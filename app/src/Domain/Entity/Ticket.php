<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\TicketId;
use App\Domain\ValueObject\TicketStatus;

class Ticket
{
    private string $id;
    private Order $order;
    private string $eventSeatId;
    private Price $price;
    private TicketCode $code;
    private TicketStatus $status;

    private function __construct(TicketId $id, Order $order, EventSeatId $eventSeatId, Price $price, TicketCode $code)
    {
        $this->id = $id->toString();
        $this->order = $order;
        $this->eventSeatId = $eventSeatId->toString();
        // Price is snapshotted at purchase time: later price changes on the
        // seat must not rewrite the history of already sold tickets.
        $this->price = $price;
        $this->code = $code;
        $this->status = TicketStatus::Reserved;
    }

    public static function create(
        Order $order,
        EventSeatId $eventSeatId,
        Price $priceAtPurchase,
        TicketCode $code,
        IdGeneratorInterface $ids,
    ): self {
        return new self(new TicketId($ids->generate()), $order, $eventSeatId, $priceAtPurchase, $code);
    }

    public function id(): TicketId
    {
        return new TicketId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function order(): Order
    {
        return $this->order;
    }

    /**
     * Reference to the EventSeat aggregate by ID (cross-aggregate boundary).
     */
    public function eventSeatId(): EventSeatId
    {
        return new EventSeatId($this->eventSeatId);
    }

    /**
     * Price fixed at the moment of purchase.
     */
    public function price(): Price
    {
        return $this->price;
    }

    public function code(): TicketCode
    {
        return $this->code;
    }

    public function status(): TicketStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        if ($this->status !== TicketStatus::Reserved) {
            throw new BusinessRuleViolationException('Only reserved tickets can be activated.');
        }
        $this->status = TicketStatus::Active;
    }

    public function cancel(): void
    {
        if ($this->status !== TicketStatus::Reserved && $this->status !== TicketStatus::Active) {
            throw new BusinessRuleViolationException('Only reserved tickets can be cancelled.');
        }
        $this->status = TicketStatus::Cancelled;
    }

    public function refund(): void
    {
        if ($this->status !== TicketStatus::Active) {
            throw new BusinessRuleViolationException('Only active tickets can be refunded.');
        }
        $this->status = TicketStatus::Refunded;
    }
}
