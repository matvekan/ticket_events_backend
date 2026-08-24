<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Dto;

final readonly class MessageFrame implements IncomingFrame
{
    public function __construct(
        private string $roomId,
        private string $text,
    ) {}

    public function roomId(): string { return $this->roomId; }

    public function text(): string { return $this->text; }
}
