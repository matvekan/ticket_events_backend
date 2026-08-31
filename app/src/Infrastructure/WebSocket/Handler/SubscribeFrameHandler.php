<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Handler;

use Amp\Websocket\WebsocketClient;
use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\User;
use App\Infrastructure\WebSocket\ChatSubscriptions;

final class SubscribeFrameHandler
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatSubscriptions $subscriptions,
    ) {
    }

    public function handle(WebsocketClient $client, User $user, \App\Infrastructure\WebSocket\Dto\SubscribeFrame $frame): void
    {
        if (!$this->chatService->canAccess($frame->roomId(), $user)) {
            $this->sendError($client, 'Access denied to chat room.');
            return;
        }

        $this->subscriptions->subscribe($client, $frame->roomId());
        $this->sendJson($client, ['type' => 'subscribed', 'roomId' => $frame->roomId()]);
    }

    private function sendJson(WebsocketClient $client, array $data): void
    {
        $client->sendText(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    private function sendError(WebsocketClient $client, string $message): void
    {
        $this->sendJson($client, ['type' => 'error', 'message' => $message]);
    }
}