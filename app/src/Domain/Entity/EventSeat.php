<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\SeatStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_seats')]
class EventSeat extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'eventSeats')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\ManyToOne(targetEntity: Seat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Seat $seat;

    #[ORM\Column(type: 'integer')]
    private int $priceAmount;

    #[ORM\Column(length: 3)]
    private string $priceCurrency;

    #[ORM\Column(type: 'string', enumType: SeatStatus::class, length: 20)]
    private SeatStatus $status;

    public function __construct(Event $event, Seat $seat, int $priceAmount, string $priceCurrency = 'RUB')
    {
        $this->event = $event;
        $this->seat = $seat;
        $this->priceAmount = $priceAmount;
        $this->priceCurrency = $priceCurrency;
        $this->status = SeatStatus::Free;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    public function getSeat(): Seat
    {
        return $this->seat;
    }

    public function getPriceAmount(): int
    {
        return $this->priceAmount;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function getPriceAsFloat(): float
    {
        return $this->priceAmount / 100;
    }

    public function getStatus(): SeatStatus
    {
        return $this->status;
    }

    public function isAvailable(): bool
    {
        return $this->status === SeatStatus::Free;
    }

    public function reserve(): void
    {
        if ($this->status !== SeatStatus::Free) {
            throw new \DomainException('Seat is not available for reservation.');
        }

        $this->status = SeatStatus::Reserved;
    }

    public function sell(): void
    {
        if ($this->status !== SeatStatus::Reserved) {
            throw new \DomainException('Only reserved seats can be sold.');
        }

        $this->status = SeatStatus::Sold;
    }

    public function release(): void
    {
        if ($this->status !== SeatStatus::Reserved) {
            throw new \DomainException('Only reserved seats can be released.');
        }

        $this->status = SeatStatus::Free;
    }

    public function unsell(): void
    {
        if ($this->status !== SeatStatus::Sold) {
            throw new \DomainException('Only sold seats can be unsold.');
        }

        $this->status = SeatStatus::Free;
    }
}
