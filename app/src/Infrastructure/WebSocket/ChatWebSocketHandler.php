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

                $this->dispatch($client, $user, $message->buffer());
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

    private function dispatch(WebsocketClient $client, User $user, string $payload): void
    {
        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->sendError($client, 'Malformed JSON.');

            return;
        }

        $type = $data['type'] ?? null;

        match ($type) {
            'subscribe' => $this->subscribe($client, $user, $data),
            'unsubscribe' => $this->unsubscribe($client, $data),
            'message' => $this->sendMessage($client, $user, $data),
            default => $this->sendError($client, 'Unknown message type.'),
        };
    }

    /** @param array<string, mixed> $data */
    private function subscribe(WebsocketClient $client, User $user, array $data): void
    {
        $roomId = $this->parseRoomId($data);
        if ($roomId === null || !$this->chatService->canAccess($roomId, $user)) {
            $this->sendError($client, 'Access denied to chat room.');

            return;
        }

        $this->subscriptions->subscribe($client, $roomId->toRfc4122());
        $this->sendJson($client, ['type' => 'subscribed', 'roomId' => $roomId->toRfc4122()]);
    }

    /** @param array<string, mixed> $data */
    private function unsubscribe(WebsocketClient $client, array $data): void
    {
        $roomId = $this->parseRoomId($data);
        if ($roomId === null) {
            $this->sendError($client, 'Invalid roomId.');

            return;
        }

        $this->subscriptions->unsubscribe($client, $roomId->toRfc4122());
    }

    /** @param array<string, mixed> $data */
    private function sendMessage(WebsocketClient $client, User $user, array $data): void
    {
        $roomId = $this->parseRoomId($data);
        $text = $data['text'] ?? null;

        if ($roomId === null || !is_string($text)) {
            $this->sendError($client, 'Invalid message payload.');

            return;
        }

        try {
            $message = $this->chatService->sendMessage($roomId, $user->id(), $text);
        } catch (\Throwable $exception) {
            $this->sendError($client, 'Message rejected: ' . $exception->getMessage());

            return;
        }

        $payload = json_encode([
            'type' => 'message',
            'message' => $this->messageFactory->fromMessage($message),
        ], JSON_UNESCAPED_UNICODE);
        $this->subscriptions->broadcast($roomId->toRfc4122(), $payload);
    }

    /** @param array<string, mixed> $data */
    private function parseRoomId(array $data): ?Uuid
    {
        $roomId = $data['roomId'] ?? null;
        if (!is_string($roomId) || $roomId === '') {
            return null;
        }

        try {
            return Uuid::fromString($roomId);
        } catch (\InvalidArgumentException) {
            return null;
        }
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