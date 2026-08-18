<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CancelEventCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class CancelEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventRepositoryInterface $events,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CancelEventCommand $command): void
    {
        $domainEvents = $this->transactionManager->transactional(function () use ($command): array {
            $event = $this->events->findById(Uuid::fromString($command->eventId));
            if (!$event) {
                throw new EntityNotFoundException('Event not found.');
            }

            $event->cancel();
            $this->events->save($event);

            return $event->releaseEvents();
        });

        foreach ($domainEvents as $domainEvent) {
            $this->eventBus->dispatch($domainEvent);
        }
    }
}