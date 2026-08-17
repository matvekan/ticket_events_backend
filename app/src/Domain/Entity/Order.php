<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\EventRecordingCapability;
use App\Domain\Event\OrderCancelledEvent;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Event\OrderRefundedEvent;
use App\Domain\Event\SeatsReservedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Price;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Order
{
    use EventRecordingCapability;
    private Uuid $id;
    private User $user;
    private Price $totalPrice;
    private OrderStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    /** @var Collection<int, Ticket> */
    private Collection $tickets;

    private function __construct(User $user)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->totalPrice = Price::fromAmount(0);
        $this->status = OrderStatus::Pending;
        $this->createdAt = new \DateTimeImmutable();
        $this->tickets = new ArrayCollection();
    }

    public static function create(User $user): self
    {
        return new self($user);
    }

    public function markSeatsAsReserved(): void
    {
        $eventSeatIds = $this->tickets
            ->map(fn (Ticket $ticket): Uuid => $ticket->eventSeat()->id())
            ->toArray();
        $this->recordThat(new SeatsReservedEvent($this->id, $this->user->id(), $eventSeatIds));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function user(): User
    {
        return $this->user;
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

    /** @return Collection<int, Ticket> */
    public function tickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): void
    {
        if ($this->tickets->contains($ticket)) {
            return;
        }

        $first = $this->tickets->first();
        if ($first !== false && $first->price()->currency() !== $ticket->price()->currency()) {
            throw new BusinessRuleViolationException('All tickets in an order must share the same currency.');
        }

        $this->tickets->add($ticket);
        $this->recalculateTotalPrice();
    }

    public function removeTicket(Ticket $ticket): void
    {
        if (!$this->tickets->removeElement($ticket)) {
            return;
        }

        $this->recalculateTotalPrice();
    }

    private function recalculateTotalPrice(): void
    {
        $total = 0;
        foreach ($this->tickets as $ticket) {
            $total += $ticket->price()->amount();
        }

        $this->totalPrice = Price::fromAmount($total, $this->totalPrice->currency());
    }

    public function pay(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending orders can be paid.');
        }

        if ($this->tickets->isEmpty() || $this->totalPrice->amount() <= 0) {
            throw new BusinessRuleViolationException('Cannot pay an order without tickets.');
        }

        $this->status = OrderStatus::Paid;
        $this->updatedAt = new \DateTimeImmutable();

        $ticketIds = array_map(fn (Ticket $ticket): Uuid => $ticket->id(), $this->tickets->toArray());
        $this->recordThat(new OrderPaidEvent(
            $this->id,
            $this->user->id(),
            $this->totalPrice->amount(),
            $ticketIds,
        ));
    }

    public function cancel(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending orders can be cancelled.');
        }

        if ($this->tickets->isEmpty()) {
            throw new BusinessRuleViolationException('Cannot cancel an order without tickets.');
        }

        $this->status = OrderStatus::Cancelled;
        $this->updatedAt = new \DateTimeImmutable();

        $eventSeatIds = $this->tickets
            ->map(fn (Ticket $ticket): Uuid => $ticket->eventSeat()->id())
            ->toArray();
        $this->recordThat(new OrderCancelledEvent($this->id, $this->user->id(), $eventSeatIds));
    }

    public function refund(): void
    {
        if ($this->status !== OrderStatus::Paid) {
            throw new BusinessRuleViolationException('Only paid orders can be refunded.');
        }

        if ($this->tickets->isEmpty()) {
            throw new BusinessRuleViolationException('Cannot refund an order without tickets.');
        }

        $this->status = OrderStatus::Refunded;
        $this->updatedAt = new \DateTimeImmutable();

        $eventSeatIds = $this->tickets
            ->map(fn (Ticket $ticket): Uuid => $ticket->eventSeat()->id())
            ->toArray();
        $this->recordThat(new OrderRefundedEvent(
            $this->id,
            $this->user->id(),
            $this->totalPrice->amount(),
            $eventSeatIds,
        ));
    }
}
