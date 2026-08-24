<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Dto;

/**
 * Base contract for parsed incoming WebSocket frames.
 */
interface IncomingFrame
{
    public function roomId(): string;
}
