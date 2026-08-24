<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function dispatch(CommandInterface $command): mixed
    {
        try {
            $envelope = $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $handlerException) {
            throw $handlerException->getPrevious() ?? $handlerException;
        }

        $handledStamp = $envelope->last(HandledStamp::class);
        if (!$handledStamp instanceof HandledStamp) {
            return null;
        }

        return $handledStamp->getResult();
    }
}
