<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Handler;

use Amp\Websocket\WebsocketClient;
use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\User;
use App\Infrastructure\WebSocket\ChatSubscriptions;
use App\Infrastructure\WebSocket\Dto\SubscribeFrame;
use JsonException;

final class SubscribeFrameHandler
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatSubscriptions $subscriptions,
    ) {
    }

    public function handle(WebsocketClient $client, User $user, SubscribeFrame $frame): void
    {
        if (!$this->chatService->canAccess($frame->roomId(), $user)) {
            $this->sendError($client, 'Access denied to chat room.');

            return;
        }

        $this->subscriptions->subscribe($client, $frame->roomId());
        $this->sendJson($client, ['type' => 'subscribed', 'roomId' => $frame->roomId()]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function sendJson(WebsocketClient $client, array $data): void
    {
        try {
            $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        $client->sendText($payload);
    }

    private function sendError(WebsocketClient $client, string $message): void
    {
        $this->sendJson($client, ['type' => 'error', 'message' => $message]);
    }
}
