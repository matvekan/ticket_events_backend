<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\EventCreatedEvent;
use App\Domain\Event\EventRecordingCapability;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\EventTitle;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Event
{
    use EventRecordingCapability;
    private Uuid $id;
    private EventTitle $title;
    private EventDescription $description;
    private \DateTimeImmutable $date;
    private Venue $venue;
    private EventStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, EventSeat> */
    private Collection $eventSeats;

    private function __construct(
        EventTitle $title,
        EventDescription $description,
        \DateTimeImmutable $date,
        Venue $venue
    ) {
        $this->id = Uuid::v7();
        $this->title = $title;
        $this->description = $description;

        if ($date < new \DateTimeImmutable()) {
            throw new BusinessRuleViolationException('Event date must be in the future.');
        }

        $this->date = $date;
        $this->venue = $venue;
        $this->status = EventStatus::Draft;
        $this->createdAt = new \DateTimeImmutable();
        $this->eventSeats = new ArrayCollection();

        $this->recordThat(new EventCreatedEvent($this->id, (string) $this->title));
    }

    public static function create(
        EventTitle $title,
        EventDescription $description,
        \DateTimeImmutable $date,
        Venue $venue
    ): self {
        return new self($title, $description, $date, $venue);
    }

    public function id(): Uuid
    {
        return $this->id;
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

    public function publish(): void
    {
        if ($this->status !== EventStatus::Draft) {
            throw new BusinessRuleViolationException('Only draft events can be published.');
        }

        if ($this->date < new \DateTimeImmutable()) {
            throw new BusinessRuleViolationException('Cannot publish an event in the past.');
        }

        $this->status = EventStatus::Published;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordThat(new EventStatusChangedEvent(
            $this->id,
            (string) $this->title,
            $this->status->value,
        ));
    }

    public function cancel(): void
    {
        if ($this->status === EventStatus::Cancelled) {
            throw new BusinessRuleViolationException('Event is already cancelled.');
        }

        $this->status = EventStatus::Cancelled;
        $this->updatedAt = new \DateTimeImmutable();

        $this->recordThat(new EventStatusChangedEvent(
            $this->id,
            (string) $this->title,
            $this->status->value,
        ));
    }

    /** @return Collection<int, EventSeat> */
    public function eventSeats(): Collection
    {
        return $this->eventSeats;
    }

    public function addEventSeat(EventSeat $eventSeat): void
    {
        if (!$this->eventSeats->contains($eventSeat)) {
            $this->eventSeats->add($eventSeat);
            $eventSeat->assignToEvent($this);
        }
    }
}
