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
    private function __construct(
        private readonly TicketId $id,
        private Order $order,
        private readonly EventSeatId $eventSeatId,
        private readonly Price $price,
        private readonly TicketCode $code,
        private TicketStatus $status,
    ) {
        // Price is snapshotted at purchase time: later price changes on the
        // seat must not rewrite the history of already sold tickets.
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
        return $this->id;
    }

    public function rawId(): string { return $this->id->toString(); }

    public function order(): Order
    {
        return $this->order;
    }

    /**
     * Reference to the EventSeat aggregate by ID (cross-aggregate boundary).
     */
    public function eventSeatId(): EventSeatId
    {
        return $this->eventSeatId;
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
