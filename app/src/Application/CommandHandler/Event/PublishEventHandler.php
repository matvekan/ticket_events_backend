<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Event\EventBusInterface;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PublishEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepo,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(PublishEventCommand $command): void
    {
        $event = $this->eventRepo->findById($command->eventId);
        if (!$event) {
            throw new \DomainException('Event not found.');
        }

        $event->publish();
        $this->eventRepo->save($event);

        $this->eventBus->dispatch(new EventStatusChangedEvent(
            $event->getId(),
            $event->getTitle(),
            $event->getStatus()->value,
        ));
    }
}
