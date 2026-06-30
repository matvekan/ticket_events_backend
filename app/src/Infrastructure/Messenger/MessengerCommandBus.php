<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\CommandInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function dispatch(CommandInterface $command): mixed
    {
        return $this->commandBus->dispatch($command);
    }
}
