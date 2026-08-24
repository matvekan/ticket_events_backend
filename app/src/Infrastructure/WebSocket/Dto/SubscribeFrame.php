<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Dto;

final readonly class SubscribeFrame implements IncomingFrame
{
    public function __construct(private string $roomId) {}

    public function roomId(): string { return $this->roomId; }
}
