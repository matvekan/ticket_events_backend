<?php

declare(strict_types=1);

namespace App\Infrastructure\EventBus;

use App\Application\Event\EventBusInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $eventBus,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
