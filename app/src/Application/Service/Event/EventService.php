<?php

declare(strict_types=1);

namespace App\Application\Service\Event;

use App\Application\Dto\EventSeatData;
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
use Symfony\Component\Uid\Uuid;

final readonly class EventService
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private SeatRepositoryInterface $seats,
        private EventRepositoryInterface $events,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    /** @param EventSeatData[] $seatsData */
    public function create(string $title, string $description, \DateTimeImmutable $date, Uuid $venueId, array $seatsData): void
    {
        $result = $this->transactionManager->transactional(function () use (
            $title,
            $description,
            $date,
            $venueId,
            $seatsData,
        ): array {
            $venue = $this->venues->findById($venueId);
            if (!$venue) {
                throw new EntityNotFoundException('Venue not found.');
            }

            if ($seatsData === []) {
                throw new BusinessRuleViolationException('At least one seat must be selected.');
            }

            $event = Event::create(
                new EventTitle($title),
                new EventDescription($description),
                $date,
                $venue,
            );

            foreach ($seatsData as $seatData) {
                $seat = $this->seats->findById($seatData->seatId);
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

        foreach ($result as $domainEvent) {
            $this->eventBus->dispatch($domainEvent);
        }
    }

    public function publish(Uuid $eventId): void
    {
        $this->transactionManager->transactional(function () use ($eventId): void {
            $event = $this->events->findById($eventId);
            if (!$event) {
                throw new EntityNotFoundException('Event not found.');
            }

            $event->publish();
            $this->events->save($event);
        });

        $event = $this->events->findById($eventId);
        if ($event !== null) {
            foreach ($event->releaseEvents() as $domainEvent) {
                $this->eventBus->dispatch($domainEvent);
            }
        }
    }

    public function cancel(Uuid $eventId): void
    {
        $this->transactionManager->transactional(function () use ($eventId): void {
            $event = $this->events->findById($eventId);
            if (!$event) {
                throw new EntityNotFoundException('Event not found.');
            }

            $event->cancel();
            $this->events->save($event);
        });

        $event = $this->events->findById($eventId);
        if ($event !== null) {
            foreach ($event->releaseEvents() as $domainEvent) {
                $this->eventBus->dispatch($domainEvent);
            }
        }
    }
}
