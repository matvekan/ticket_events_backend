<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Handler;

use Amp\Websocket\WebsocketClient;
use App\Infrastructure\WebSocket\ChatSubscriptions;
use App\Infrastructure\WebSocket\Dto\UnsubscribeFrame;

final class UnsubscribeFrameHandler
{
    public function __construct(
        private readonly ChatSubscriptions $subscriptions,
    ) {
    }

    public function handle(WebsocketClient $client, UnsubscribeFrame $frame): void
    {
        $this->subscriptions->unsubscribe($client, $frame->roomId());
    }
}