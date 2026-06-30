<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\EventStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'events')]
class Event extends AbstractEntity
{
    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\ManyToOne(targetEntity: Venue::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Venue $venue;

    #[ORM\Column(type: 'string', enumType: EventStatus::class, length: 20)]
    private EventStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Collection<int, EventSeat> */
    #[ORM\OneToMany(targetEntity: EventSeat::class, mappedBy: 'event', cascade: ['persist'])]
    private Collection $eventSeats;

    public function __construct(
        string $title,
        string $description,
        \DateTimeImmutable $date,
        Venue $venue
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
        $this->venue = $venue;
        $this->status = EventStatus::Draft;
        $this->createdAt = new \DateTimeImmutable();
        $this->eventSeats = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getVenue(): Venue
    {
        return $this->venue;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function publish(): void
    {
        if ($this->status !== EventStatus::Draft) {
            throw new \DomainException('Only draft events can be published.');
        }

        $this->status = EventStatus::Published;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === EventStatus::Cancelled) {
            throw new \DomainException('Event is already cancelled.');
        }

        $this->status = EventStatus::Cancelled;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markSoldOut(): void
    {
        if ($this->status !== EventStatus::Published) {
            throw new \DomainException('Only published events can be marked as sold out.');
        }

        $this->status = EventStatus::SoldOut;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @return Collection<int, EventSeat> */
    public function getEventSeats(): Collection
    {
        return $this->eventSeats;
    }

    public function addEventSeat(EventSeat $eventSeat): void
    {
        if (!$this->eventSeats->contains($eventSeat)) {
            $this->eventSeats->add($eventSeat);
            $eventSeat->setEvent($this);
        }
    }
}
