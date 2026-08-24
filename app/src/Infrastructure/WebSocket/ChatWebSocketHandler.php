<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\WebsocketClient;
use Amp\Websocket\WebsocketCloseCode;
use App\Application\Dto\Factory\ChatMessageDtoFactory;
use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\User;
use App\Infrastructure\WebSocket\Dto\FrameParser;
use App\Infrastructure\WebSocket\Dto\IncomingFrame;
use App\Infrastructure\WebSocket\Dto\MessageFrame;
use App\Infrastructure\WebSocket\Dto\SubscribeFrame;
use App\Infrastructure\WebSocket\Dto\UnsubscribeFrame;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class ChatWebSocketHandler implements WebsocketClientHandler
{
    public function __construct(
        private readonly ChatAuthenticator $authenticator,
        private readonly ChatSubscriptions $subscriptions,
        private readonly ChatService $chatService,
        private readonly ChatMessageDtoFactory $messageFactory,
        private readonly FrameParser $frameParser,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handleClient(WebsocketClient $client, Request $request, Response $response): void
    {
        $user = $this->authenticator->authenticate($request);
        if ($user === null) {
            $client->close(WebsocketCloseCode::POLICY_VIOLATION, 'Unauthorized');
            $this->logger->warning('Chat connection rejected: invalid token.');

            return;
        }

        $this->logger->info(sprintf('Chat client connected: %s (%s)', (string) $user->email(), (string) $user->id()));

        try {
            foreach ($client as $message) {
                if ($message->isBinary()) {
                    continue;
                }

                $this->handleFrame($client, $user, $message->buffer());
                $this->entityManager->clear();
            }
        } catch (\Throwable $exception) {
            $this->logger->error(sprintf(
                'Chat handler error: %s @ %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));

            throw $exception;
        } finally {
            $this->subscriptions->detach($client);
            $this->logger->info(sprintf('Chat client disconnected: %s', (string) $user->email()));
        }
    }

    private function handleFrame(WebsocketClient $client, User $user, string $payload): void
    {
        $frame = $this->frameParser->parse($payload);

        if ($frame === null) {
            $this->sendError($client, 'Malformed or unsupported frame.');

            return;
        }

        match (true) {
            $frame instanceof MessageFrame => $this->sendMessage($client, $user, $frame),
            $frame instanceof SubscribeFrame => $this->subscribe($client, $user, $frame),
            $frame instanceof UnsubscribeFrame => $this->unsubscribe($client, $frame),
            default => $this->sendError($client, 'Unknown frame type.'),
        };
    }

    private function subscribe(WebsocketClient $client, User $user, IncomingFrame $frame): void
    {
        if (!$this->chatService->canAccess(Uuid::fromString($frame->roomId()), $user)) {
            $this->sendError($client, 'Access denied to chat room.');

            return;
        }

        $this->subscriptions->subscribe($client, $frame->roomId());
        $this->sendJson($client, ['type' => 'subscribed', 'roomId' => $frame->roomId()]);
    }

    private function unsubscribe(WebsocketClient $client, IncomingFrame $frame): void
    {
        $this->subscriptions->unsubscribe($client, $frame->roomId());
    }

    private function sendMessage(WebsocketClient $client, User $user, MessageFrame $frame): void
    {
        try {
            $message = $this->chatService->sendMessage(
                Uuid::fromString($frame->roomId()),
                $user->id(),
                $frame->text(),
            );
        } catch (\Throwable $exception) {
            $this->sendError($client, 'Message rejected: ' . $exception->getMessage());

            return;
        }

        $payload = json_encode([
            'type' => 'message',
            'message' => $this->messageFactory->fromMessage($message),
        ], JSON_UNESCAPED_UNICODE);

        $this->subscriptions->broadcast($frame->roomId(), $payload);
    }

    /** @param array<string, mixed> $data */
    private function sendJson(WebsocketClient $client, array $data): void
    {
        $client->sendText(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function sendError(WebsocketClient $client, string $message): void
    {
        $this->sendJson($client, ['type' => 'error', 'message' => $message]);
    }
}
