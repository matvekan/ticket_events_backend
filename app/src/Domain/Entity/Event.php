<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\EventCancelledEvent;
use App\Domain\Event\EventCreatedEvent;
use App\Domain\Event\EventRecordingCapability;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\EventTitle;

class Event
{
    use EventRecordingCapability;

    private function __construct(
        private EventId $id,
        private EventTitle $title,
        private EventDescription $description,
        private \DateTimeImmutable $date,
        private Venue $venue,
        private EventStatus $status,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
        private iterable $eventSeats = [],
    ) {
    }

    public static function create(
        EventTitle $title,
        EventDescription $description,
        \DateTimeImmutable $date,
        Venue $venue,
        ClockInterface $clock,
        IdGeneratorInterface $ids,
    ): self {
        if ($date < $clock->now()) {
            throw new BusinessRuleViolationException('Event date must be in the future.');
        }

        $event = new self(
            new EventId($ids->generate()),
            $title,
            $description,
            $date,
            $venue,
            EventStatus::Draft,
            $clock->now(),
        );
        $event->recordThat(new EventCreatedEvent($event->id->toString(), (string) $event->title));

        return $event;
    }

    public function id(): EventId
    {
        return $this->id;
    }

    public function rawId(): string
    {
        return $this->id->toString();
    }

    public function title(): EventTitle
    {
        return $this->title;
    }

    public function description(): EventDescription
    {
        return $this->description;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function venue(): Venue
    {
        return $this->venue;
    }

    public function status(): EventStatus
    {
        return $this->status;
    }

    public function publish(ClockInterface $clock): void
    {
        if ($this->status !== EventStatus::Draft) {
            throw new BusinessRuleViolationException('Only draft events can be published.');
        }

        if ($this->date < $clock->now()) {
            throw new BusinessRuleViolationException('Cannot publish an event in the past.');
        }

        if ($this->eventSeatList() === []) {
            throw new BusinessRuleViolationException('Cannot publish an event without seats.');
        }

        $this->status = EventStatus::Published;
        $this->updatedAt = $clock->now();

        $this->recordThat(new EventStatusChangedEvent(
            $this->id->toString(),
            (string) $this->title,
            $this->status->value,
        ));
    }

    public function ensureCanBeBooked(ClockInterface $clock): void
    {
        if ($this->status !== EventStatus::Published) {
            throw new BusinessRuleViolationException('Event is not published.');
        }

        if ($this->date <= $clock->now()) {
            throw new BusinessRuleViolationException('Event has already occurred or is in the past.');
        }
    }

    public function cancel(ClockInterface $clock): void
    {
        if ($this->status === EventStatus::Cancelled) {
            throw new BusinessRuleViolationException('Event is already cancelled.');
        }

        $this->status = EventStatus::Cancelled;
        $this->updatedAt = $clock->now();

        $this->recordThat(new EventStatusChangedEvent(
            $this->id->toString(),
            (string) $this->title,
            $this->status->value,
        ));

        $this->recordThat(new EventCancelledEvent(
            $this->id->toString(),
            (string) $this->title,
        ));
    }

    public function eventSeats(): array
    {
        return $this->eventSeatList();
    }

    private function eventSeatList(): array
    {
        if (is_array($this->eventSeats)) {
            return $this->eventSeats;
        }

        $seats = iterator_to_array($this->eventSeats);
        $this->eventSeats = $seats;

        return $seats;
    }
}
