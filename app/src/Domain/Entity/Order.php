<?php

declare(strict_types=1);

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
use App\Domain\ValueObject\UserId;

class Order
{
    use EventRecordingCapability;
    private string $id;
    private string $userId;
    private Price $totalPrice;
    private OrderStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt = null;

    /** @var Ticket[]|\Traversable<int, Ticket>|null */
    private $tickets = [];

    private function __construct(OrderId $id, UserId $userId, \DateTimeImmutable $createdAt)
    {
        $this->id = $id->toString();
        $this->userId = $userId->toString();
        $this->totalPrice = Price::fromAmount(0);
        $this->status = OrderStatus::Pending;
        $this->createdAt = $createdAt;
    }

    public static function create(UserId $userId, ClockInterface $clock, IdGeneratorInterface $ids): self
    {
        return new self(new OrderId($ids->generate()), $userId, $clock->now());
    }

    public function markSeatsAsReserved(): void
    {
        $eventSeatIds = array_map(fn (Ticket $ticket) => $ticket->eventSeat()->id()->toString(), $this->ticketList());
        $this->recordThat(new SeatsReservedEvent($this->id, $this->userId, $eventSeatIds));
    }

    public function id(): OrderId
    {
        return new OrderId($this->id);
    }

    public function rawId(): string
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return new UserId($this->userId);
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

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Ticket[] */
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

        $ticketIds = array_map(fn (Ticket $ticket) => $ticket->id()->toString(), $this->ticketList());
        $this->recordThat(new OrderPaidEvent(
            $this->id,
            $this->userId,
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

        $eventSeatIds = array_map(fn (Ticket $ticket) => $ticket->eventSeat()->id()->toString(), $this->ticketList());
        $this->recordThat(new OrderCancelledEvent($this->id, $this->userId, $eventSeatIds));
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

        $eventSeatIds = array_map(fn (Ticket $ticket) => $ticket->eventSeat()->id()->toString(), $this->ticketList());
        $this->recordThat(new OrderRefundedEvent(
            $this->id,
            $this->userId,
            $this->totalPrice->amount(),
            $eventSeatIds,
        ));
    }

    /** @return Ticket[] */
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
