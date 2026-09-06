<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Dto;

interface IncomingFrame
{
    public function roomId(): string;
}
