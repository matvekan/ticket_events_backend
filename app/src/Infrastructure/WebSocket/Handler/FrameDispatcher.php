<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Handler;

use Amp\Websocket\WebsocketClient;
use App\Domain\Entity\User;
use App\Infrastructure\WebSocket\Dto\IncomingFrame;
use App\Infrastructure\WebSocket\Dto\MessageFrame;
use App\Infrastructure\WebSocket\Dto\SubscribeFrame;
use App\Infrastructure\WebSocket\Dto\UnsubscribeFrame;
use Psr\Log\LoggerInterface;

final class FrameDispatcher
{
    public function __construct(
        private readonly MessageFrameHandler $messageHandler,
        private readonly SubscribeFrameHandler $subscribeHandler,
        private readonly UnsubscribeFrameHandler $unsubscribeHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function dispatch(WebsocketClient $client, User $user, IncomingFrame $frame): void
    {
        match (true) {
            $frame instanceof MessageFrame => $this->messageHandler->handle($client, $user, $frame),
            $frame instanceof SubscribeFrame => $this->subscribeHandler->handle($client, $user, $frame),
            $frame instanceof UnsubscribeFrame => $this->unsubscribeHandler->handle($client, $frame),
            default => $this->sendError($client, 'Unknown frame type.'),
        };
    }

    private function sendError(WebsocketClient $client, string $message): void
    {
        $payload = json_encode(['type' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
        $client->sendText($payload);
    }
}