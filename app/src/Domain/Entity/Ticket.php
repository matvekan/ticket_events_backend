<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\TicketId;
use App\Domain\ValueObject\TicketStatus;

class Ticket
{
    private string $id;
    private Order $order;
    private EventSeat $eventSeat;
    private TicketCode $code;
    private TicketStatus $status;

    private function __construct(TicketId $id, Order $order, EventSeat $eventSeat, TicketCode $code)
    {
        $this->id = $id->toString();
        $this->order = $order;
        $this->eventSeat = $eventSeat;
        $this->code = $code;
        $this->status = TicketStatus::Active;
    }

    public static function create(Order $order, EventSeat $eventSeat, TicketCode $code, ?IdGeneratorInterface $ids = null, ?TicketId $id = null): self
    {
        $ticketId = $id ?? new TicketId($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        return new self($ticketId, $order, $eventSeat, $code);
    }

    public function id(): TicketId
    {
        return new TicketId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function eventSeat(): EventSeat
    {
        return $this->eventSeat;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function code(): TicketCode
    {
        return $this->code;
    }

    public function status(): TicketStatus
    {
        return $this->status;
    }

    public function cancel(): void
    {
        if ($this->status !== TicketStatus::Active) {
            throw new \DomainException('Only active tickets can be cancelled.');
        }
        $this->status = TicketStatus::Cancelled;
    }

    public function refund(): void
    {
        if ($this->status !== TicketStatus::Active) {
            throw new \DomainException('Only active tickets can be refunded.');
        }
        $this->status = TicketStatus::Refunded;
    }

    public function price(): Price
    {
        return $this->eventSeat->price();
    }
}
