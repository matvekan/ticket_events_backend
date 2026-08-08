<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketCode;
use Symfony\Component\Uid\Uuid;

class Ticket
{
    private Uuid $id;
    private Order $order;
    private EventSeat $eventSeat;
    private TicketCode $code;

    private function __construct(Order $order, EventSeat $eventSeat, TicketCode $code)
    {
        $this->id = Uuid::v7();
        $this->order = $order;
        $this->eventSeat = $eventSeat;
        $this->code = $code;
    }

    public static function create(Order $order, EventSeat $eventSeat, TicketCode $code): self
    {
        return new self($order, $eventSeat, $code);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

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

    public function price(): Price
    {
        return $this->eventSeat->price();
    }
}
