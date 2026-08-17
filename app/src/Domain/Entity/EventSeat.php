<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\SeatStatus;
use Symfony\Component\Uid\Uuid;

class EventSeat
{
    private Uuid $id;
    private Event $event;
    private Seat $seat;
    private Price $price;
    private SeatStatus $status;

    private function __construct(Event $event, Seat $seat, Price $price)
    {
        if (!$seat->venue()->id()->equals($event->venue()->id())) {
            throw new BusinessRuleViolationException('Seat does not belong to the event venue.');
        }

        $this->id = Uuid::v7();
        $this->event = $event;
        $this->seat = $seat;
        $this->price = $price;
        $this->status = SeatStatus::Free;
    }

    public static function create(
        Event $event,
        Seat $seat,
        int $priceAmount,
        string $priceCurrency = 'RUB',
    ): self {
        return new self($event, $seat, Price::fromAmount($priceAmount, $priceCurrency));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

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
