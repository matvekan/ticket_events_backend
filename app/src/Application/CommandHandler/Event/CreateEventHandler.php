<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CreateEventCommand;
use App\Application\Message\CreateEventSeatsMessage;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Event;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\VenueId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class CreateEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private EventRepositoryInterface $events,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(CreateEventCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $venue = $this->venues->findById(new VenueId($command->venueId));
            if (!$venue) {
                throw new EntityNotFoundException('Venue not found.');
            }

            $event = Event::create(
                new EventTitle($command->title),
                new EventDescription($command->description),
                $command->date,
                $venue,
                $this->clock,
                $this->ids,
            );

            $this->events->save($event);

            $this->messageBus->dispatch(new CreateEventSeatsMessage(
                $event->id()->toString(),
                $venue->id()->toString(),
                $command->seats,
            ));
        });
    }
}
