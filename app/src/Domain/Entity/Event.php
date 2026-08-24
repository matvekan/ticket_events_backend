<?php

declare(strict_types=1);

namespace App\Domain\Entity;

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
    private string $id;
    private EventTitle $title;
    private EventDescription $description;
    private \DateTimeImmutable $date;
    private Venue $venue;
    private EventStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var EventSeat[] */
    private $eventSeats = [];

    private function __construct(
        EventId $id,
        EventTitle $title,
        EventDescription $description,
        \DateTimeImmutable $date,
        Venue $venue,
        ClockInterface $clock
    ) {
        $this->id = $id->toString();
        $this->title = $title;
        $this->description = $description;

        if ($date < $clock->now()) {
            throw new BusinessRuleViolationException('Event date must be in the future.');
        }

        $this->date = $date;
        $this->venue = $venue;
        $this->status = EventStatus::Draft;
        $this->createdAt = $clock->now();

        $this->recordThat(new EventCreatedEvent($this->id, (string) $this->title));
    }

    public static function create(
        EventTitle $title,
        EventDescription $description,
        \DateTimeImmutable $date,
        Venue $venue,
        ClockInterface $clock,
        IdGeneratorInterface $ids
    ): self {
        return new self(new EventId($ids->generate()), $title, $description, $date, $venue, $clock);
    }

    public function id(): EventId
    {
        return new EventId($this->id);
    }

    public function rawId(): string { return $this->id; }

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
            $this->id,
            (string) $this->title,
            $this->status->value,
        ));
    }

    public function cancel(ClockInterface $clock): void
    {
        if ($this->status === EventStatus::Cancelled) {
            throw new BusinessRuleViolationException('Event is already cancelled.');
        }

        $this->status = EventStatus::Cancelled;
        $this->updatedAt = $clock->now();

        $this->recordThat(new EventStatusChangedEvent(
            $this->id,
            (string) $this->title,
            $this->status->value,
        ));
    }

    /** @return EventSeat[] */
    public function eventSeats(): array
    {
        return $this->eventSeatList();
    }

    public function addEventSeat(EventSeat $eventSeat): void
    {
        foreach ($this->eventSeatList() as $existing) {
            if ($existing === $eventSeat) {
                return;
            }
        }
        $seats = $this->eventSeatList();
        $seats[] = $eventSeat;
        $this->eventSeats = $seats;
        $eventSeat->assignToEvent($this);
    }

    /** @return EventSeat[] */
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
