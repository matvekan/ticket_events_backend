<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\EventId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventRepositoryInterface $events,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(PublishEventCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $event = $this->events->findById(new EventId($command->eventId));
            if (!$event) {
                throw new EntityNotFoundException('Event not found.');
            }

            $event->publish($this->clock);
            $this->events->save($event);
        });
    }
}
