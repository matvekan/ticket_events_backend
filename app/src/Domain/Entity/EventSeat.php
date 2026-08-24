<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\SeatStatus;

class EventSeat
{
    private string $id;
    private Event $event;
    private Seat $seat;
    private Price $price;
    private SeatStatus $status;

    private function __construct(EventSeatId $id, Event $event, Seat $seat, Price $price)
    {
        if (!$seat->venue()->id()->equals($event->venue()->id())) {
            throw new BusinessRuleViolationException('Seat does not belong to the event venue.');
        }

        $this->id = $id->toString();
        $this->event = $event;
        $this->seat = $seat;
        $this->price = $price;
        $this->status = SeatStatus::Free;
    }

    public static function create(
        Event $event,
        Seat $seat,
        int $priceAmount,
        string $priceCurrency = 'BYN',
        ?IdGeneratorInterface $ids = null,
        ?EventSeatId $id = null,
    ): self {
        $eventSeatId = $id ?? new EventSeatId($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        return new self($eventSeatId, $event, $seat, Price::fromAmount($priceAmount, $priceCurrency));
    }

    public function id(): EventSeatId
    {
        return new EventSeatId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function event(): Event
    {
        return $this->event;
    }

    public function assignToEvent(Event $event): void
    {
        if (!$this->seat->venue()->id()->equals($event->venue()->id())) {
            throw new BusinessRuleViolationException('Seat does not belong to the event venue.');
        }

        $this->event = $event;
    }

    public function seat(): Seat
    {
        return $this->seat;
    }

    public function price(): Price
    {
        return $this->price;
    }

    public function status(): SeatStatus
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
            throw new BusinessRuleViolationException('Seat is not available for reservation.');
        }

        $this->status = SeatStatus::Reserved;
    }

    public function sell(): void
    {
        if ($this->status !== SeatStatus::Reserved) {
            throw new BusinessRuleViolationException('Only reserved seats can be sold.');
        }

        $this->status = SeatStatus::Sold;
    }

    public function release(): void
    {
        if ($this->status !== SeatStatus::Reserved) {
            throw new BusinessRuleViolationException('Only reserved seats can be released.');
        }

        $this->status = SeatStatus::Free;
    }

    public function unsell(): void
    {
        if ($this->status !== SeatStatus::Sold) {
            throw new BusinessRuleViolationException('Only sold seats can be unsold.');
        }

        $this->status = SeatStatus::Free;
    }
}
