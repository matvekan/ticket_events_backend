<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tickets')]
class Ticket extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\OneToOne(targetEntity: EventSeat::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private EventSeat $eventSeat;

    #[ORM\Column(length: 255, unique: true)]
    private string $code;

    public function __construct(Order $order, EventSeat $eventSeat, string $code)
    {
        $this->order = $order;
        $this->eventSeat = $eventSeat;
        $this->code = $code;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getEventSeat(): EventSeat
    {
        return $this->eventSeat;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getPriceAmount(): int
    {
        return $this->eventSeat->getPriceAmount();
    }
}
