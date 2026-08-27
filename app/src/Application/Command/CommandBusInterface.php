<?php

declare(strict_types=1);

namespace App\Application\Command;

interface CommandBusInterface
{
    /**
     * Commands are fire-and-forget: they never return data.
     * Read back any resulting state via the query bus.
     */
    public function dispatch(CommandInterface $command): void;
}
