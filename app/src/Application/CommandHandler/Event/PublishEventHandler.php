<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class PublishEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventRepositoryInterface $events,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(PublishEventCommand $command): void
    {
        $domainEvents = $this->transactionManager->transactional(function () use ($command): array {
            $event = $this->events->findById(Uuid::fromString($command->eventId));
            if (!$event) {
                throw new EntityNotFoundException('Event not found.');
            }

            $event->publish();
            $this->events->save($event);

            return $event->releaseEvents();
        });

        foreach ($domainEvents as $domainEvent) {
            $this->eventBus->dispatch($domainEvent);
        }
    }
}