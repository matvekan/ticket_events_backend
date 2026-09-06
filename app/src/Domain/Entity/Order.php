<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\EventRecordingCapability;
use App\Domain\Event\OrderCancelledEvent;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Event\OrderRefundedEvent;
use App\Domain\Event\SeatsReservedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketStatus;
use App\Domain\ValueObject\UserId;

class Order
{
    use EventRecordingCapability;

    private function __construct(
        private OrderId $id,
        private UserId $userId,
        private Price $totalPrice,
        private OrderStatus $status,
        private \DateTimeImmutable $createdAt,
        private ?\DateTimeImmutable $updatedAt = null,
        private int $version = 1,
        private iterable $tickets = [],
    ) {
        $this->totalPrice = Price::fromAmount(0);
        $this->status = OrderStatus::Pending;
    }

    public static function create(UserId $userId, ClockInterface $clock, IdGeneratorInterface $ids): self
    {
        return new self(
            new OrderId($ids->generate()),
            $userId,
            Price::fromAmount(0),
            OrderStatus::Pending,
            $clock->now()
        );
    }

    public function markSeatsAsReserved(): void
    {
        $eventSeatIds = array_map(fn (Ticket $ticket) => $ticket->eventSeatId()->toString(), $this->ticketList());
        $this->recordThat(new SeatsReservedEvent($this->id->toString(), $this->userId->toString(), $eventSeatIds));
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function rawId(): string
    {
        return $this->id->toString();
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function totalPrice(): Price
    {
        return $this->totalPrice;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function tickets(): array
    {
        return $this->ticketList();
    }

    public function addTicket(Ticket $ticket): void
    {
        $tickets = $this->ticketList();
        foreach ($tickets as $existing) {
            if ($existing->id()->equals($ticket->id())) {
                return;
            }
        }

        if ($tickets !== [] && $tickets[0]->price()->currency() !== $ticket->price()->currency()) {
            throw new BusinessRuleViolationException('All tickets in an order must share the same currency.');
        }

        $tickets[] = $ticket;
        $this->tickets = $tickets;
        $this->recalculateTotalPrice();
    }

    private function recalculateTotalPrice(): void
    {
        $total = 0;
        foreach ($this->ticketList() as $ticket) {
            $total += $ticket->price()->amount();
        }
        $this->totalPrice = Price::fromAmount($total, $this->totalPrice->currency());
    }

    public function pay(ClockInterface $clock): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending orders can be paid.');
        }
        if ($this->ticketList() === [] || $this->totalPrice->amount() <= 0) {
            throw new BusinessRuleViolationException('Cannot pay an order without tickets.');
        }

        $this->status = OrderStatus::Paid;
        $this->updatedAt = $clock->now();

        foreach ($this->ticketList() as $ticket) {
            $ticket->activate();
        }

        $ticketIds = array_map(fn (Ticket $ticket) => $ticket->id()->toString(), $this->ticketList());
        $this->recordThat(new OrderPaidEvent(
            $this->id->toString(),
            $this->userId->toString(),
            $this->totalPrice->amount(),
            $ticketIds,
        ));
    }

    public function cancel(ClockInterface $clock): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending orders can be cancelled.');
        }
        if ($this->ticketList() === []) {
            throw new BusinessRuleViolationException('Cannot cancel an order without tickets.');
        }

        $this->status = OrderStatus::Cancelled;
        $this->updatedAt = $clock->now();

        foreach ($this->ticketList() as $ticket) {
            if ($ticket->status() === TicketStatus::Reserved || $ticket->status() === TicketStatus::Active) {
                $ticket->cancel();
            }
        }

        $eventSeatIds = array_map(fn (Ticket $ticket) => $ticket->eventSeatId()->toString(), $this->ticketList());
        $this->recordThat(new OrderCancelledEvent($this->id->toString(), $this->userId->toString(), $eventSeatIds));
    }

    public function refund(ClockInterface $clock): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new BusinessRuleViolationException('Only paid orders can be refunded.');
        }
        if ($this->ticketList() === []) {
            throw new BusinessRuleViolationException('Cannot refund an order without tickets.');
        }

        $this->status = OrderStatus::Refunded;
        $this->updatedAt = $clock->now();

        foreach ($this->ticketList() as $ticket) {
            if ($ticket->status() === TicketStatus::Active || $ticket->status() === TicketStatus::Used) {
                $ticket->refund();
            } elseif ($ticket->status() === TicketStatus::Reserved) {
                $ticket->cancel();
            }
        }

        $eventSeatIds = array_map(fn (Ticket $ticket) => $ticket->eventSeatId()->toString(), $this->ticketList());
        $this->recordThat(new OrderRefundedEvent(
            $this->id->toString(),
            $this->userId->toString(),
            $this->totalPrice->amount(),
            $eventSeatIds,
        ));
    }

    private function ticketList(): array
    {
        if (is_array($this->tickets)) {
            return $this->tickets;
        }
        $tickets = iterator_to_array($this->tickets);
        $this->tickets = $tickets;
        return $tickets;
    }
}
