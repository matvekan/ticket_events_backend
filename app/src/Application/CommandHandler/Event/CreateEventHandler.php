<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CreateEventCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventTitle;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class CreateEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private SeatRepositoryInterface $seats,
        private EventRepositoryInterface $events,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CreateEventCommand $command): void
    {
        $events = $this->transactionManager->transactional(function () use ($command): array {
            $venue = $this->venues->findById(Uuid::fromString($command->venueId));
            if (!$venue) {
                throw new EntityNotFoundException('Venue not found.');
            }

            if ($command->seats === []) {
                throw new BusinessRuleViolationException('At least one seat must be selected.');
            }

            $event = Event::create(
                new EventTitle($command->title),
                new EventDescription($command->description),
                $command->date,
                $venue,
            );

            foreach ($command->seats as $seatData) {
                $seat = $this->seats->findById(Uuid::fromString($seatData->seatId));
                if (!$seat) {
                    throw new EntityNotFoundException('Seat not found.');
                }

                if (!$seat->venue()->id()->equals($venue->id())) {
                    throw new BusinessRuleViolationException('Seat does not belong to the given venue.');
                }

                $eventSeat = EventSeat::create($event, $seat, $seatData->priceAmount);
                $event->addEventSeat($eventSeat);
            }

            $this->events->save($event);

            return $event->releaseEvents();
        });

        foreach ($events as $domainEvent) {
            $this->eventBus->dispatch($domainEvent);
        }
    }
}