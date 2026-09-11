<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Handler;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\WebsocketClient;
use Amp\Websocket\WebsocketCloseCode;
use App\Domain\Entity\User;
use App\Infrastructure\WebSocket\ChatAuthenticator;
use App\Infrastructure\WebSocket\ChatSubscriptions;
use App\Infrastructure\WebSocket\Dto\FrameParser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class ChatConnectionManager implements WebsocketClientHandler
{
    public function __construct(
        private readonly ChatAuthenticator $authenticator,
        private readonly ChatSubscriptions $subscriptions,
        private readonly FrameParser $frameParser,
        private readonly FrameDispatcher $frameDispatcher,
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

        $this->logger->info(sprintf('Chat client connected: %s (%s)', $user->email(), $user->id()));

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
            $this->logger->info(sprintf('Chat client disconnected: %s', $user->email()));
        }
    }

    private function handleFrame(WebsocketClient $client, User $user, string $payload): void
    {
        $frame = $this->frameParser->parse($payload);

        if ($frame === null) {
            $this->sendError($client, 'Malformed or unsupported frame.');

            return;
        }

        $this->frameDispatcher->dispatch($client, $user, $frame);
    }

    private function sendError(WebsocketClient $client, string $message): void
    {
        $payload = json_encode(['type' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
        $client->sendText($payload);
    }
}
