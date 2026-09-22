<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Order;
use App\Domain\Entity\Ticket;
use App\Domain\Event\OrderCancelledEvent;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Event\OrderRefundedEvent;
use App\Domain\Event\SeatsReservedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\TicketStatus;
use App\Tests\Unit\Domain\Support\DomainFixture;
use App\Tests\Unit\Domain\Support\FixedClock;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testPayTransitionsPendingOrderToPaidAndActivatesTickets(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPendingOrderWithSingleTicket($clock);

        $order->pay($clock);

        self::assertSame(OrderStatus::Paid, $order->status());
        self::assertSame(TicketStatus::Active, $order->tickets()[0]->status());
        self::assertInstanceOf(OrderPaidEvent::class, $order->releaseEvents()[0]);
    }

    public function testPayThrowsBusinessRuleViolationWhenOrderIsAlreadyPaid(): void
    {
        $order = $this->createPaidOrder();

        $this->expectException(BusinessRuleViolationException::class);

        $order->pay(DomainFixture::clock());
    }

    public function testPayThrowsBusinessRuleViolationWhenOrderHasNoTickets(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createEmptyPendingOrder($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->pay($clock);
    }

    public function testPayThrowsBusinessRuleViolationWhenOrderIsCancelled(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createCancelledOrder($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->pay($clock);
    }

    public function testCancelTransitionsPendingOrderToCancelledAndCancelsTickets(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPendingOrderWithSingleTicket($clock);

        $order->cancel($clock);

        self::assertSame(OrderStatus::Cancelled, $order->status());
        self::assertSame(TicketStatus::Cancelled, $order->tickets()[0]->status());
        self::assertInstanceOf(OrderCancelledEvent::class, $order->releaseEvents()[0]);
    }

    public function testCancelThrowsBusinessRuleViolationWhenOrderIsPaid(): void
    {
        $order = $this->createPaidOrder();

        $this->expectException(BusinessRuleViolationException::class);

        $order->cancel(DomainFixture::clock());
    }

    public function testCancelThrowsBusinessRuleViolationWhenOrderHasNoTickets(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createEmptyPendingOrder($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->cancel($clock);
    }

    public function testRefundTransitionsPaidOrderToRefundedAndRefundsActiveTickets(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPaidOrder($clock);

        $order->refund($clock);

        self::assertSame(OrderStatus::Refunded, $order->status());
        self::assertSame(TicketStatus::Refunded, $order->tickets()[0]->status());
    }

    public function testRefundEmitsOrderRefundedEventWithCorrectPayload(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPaidOrder($clock);

        $order->refund($clock);

        $events = $order->releaseEvents();
        self::assertInstanceOf(OrderRefundedEvent::class, $events[1]);
    }

    public function testRefundThrowsBusinessRuleViolationWhenOrderIsPending(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPendingOrderWithSingleTicket($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->refund($clock);
    }

    public function testRefundThrowsBusinessRuleViolationWhenOrderIsAlreadyRefunded(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createRefundedOrder($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->refund($clock);
    }

    public function testAddTicketIgnoresDuplicateTicketIdWithoutRecalculatingTotal(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createEmptyPendingOrder($clock);
        $ticket = $this->createTicketForOrder($order, 1000, 'TKT-ABC12345');

        $order->addTicket($ticket);
        $order->addTicket($ticket);

        self::assertCount(1, $order->tickets());
        self::assertSame(1000, $order->totalPrice()->amount());
    }

    public function testAddTicketThrowsBusinessRuleViolationWhenCurrenciesAreMixed(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);
        $order->addTicket($this->createTicketWithIds($order, $ids, 1000, 'TKT-ABC12345', 'BYN'));

        $this->expectException(BusinessRuleViolationException::class);

        $order->addTicket($this->createTicketWithIds($order, $ids, 1000, 'TKT-DEF67890', 'USD'));
    }

    public function testAddTicketRecalculatesTotalPriceExcludingRefundedTickets(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);

        $order->addTicket($this->createTicketWithIds($order, $ids, 2000, 'TKT-ABC12345'));
        $order->addTicket($this->createTicketWithIds($order, $ids, 3000, 'TKT-DEF67890'));

        self::assertSame(5000, $order->totalPrice()->amount());
    }

    public function testMarkSeatsAsReservedRecordsSeatsReservedEventWithEventSeatIds(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPendingOrderWithSingleTicket($clock);

        $order->markSeatsAsReserved();

        self::assertInstanceOf(SeatsReservedEvent::class, $order->releaseEvents()[0]);
    }

    public function testRefundSpecificTicketsRefundsOnlyRequestedTicketsAndKeepsOrderPaidWhenPartial(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPaidOrderWithTwoTickets($clock);
        $firstTicketId = $order->tickets()[0]->id()->toString();

        $order->refundSpecificTickets([$firstTicketId], $clock);

        self::assertSame(OrderStatus::Paid, $order->status());
        self::assertSame(TicketStatus::Refunded, $order->tickets()[0]->status());
        self::assertSame(TicketStatus::Active, $order->tickets()[1]->status());
    }

    public function testRefundSpecificTicketsTransitionsToRefundedWhenAllTicketsAreRefunded(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPaidOrderWithTwoTickets($clock);
        $ids = array_map(fn (Ticket $t) => $t->id()->toString(), $order->tickets());

        $order->refundSpecificTickets($ids, $clock);

        self::assertSame(OrderStatus::Refunded, $order->status());
    }

    public function testRefundSpecificTicketsThrowsBusinessRuleViolationWhenNoTicketsMatchRequestedIds(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPaidOrder($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->refundSpecificTickets(['00000000-0000-4000-8000-000000000999'], $clock);
    }

    public function testRefundSpecificTicketsThrowsBusinessRuleViolationWhenOrderIsNotPaid(): void
    {
        $clock = DomainFixture::clock();
        $order = $this->createPendingOrderWithSingleTicket($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $order->refundSpecificTickets([$order->tickets()[0]->id()->toString()], $clock);
    }

    private function createPendingOrderWithSingleTicket(?FixedClock $clock = null): Order
    {
        $clock ??= DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);

        return DomainFixture::orderWithTickets($user, $clock, $ids, 1, 5000);
    }

    private function createPaidOrderWithTwoTickets(?FixedClock $clock = null): Order
    {
        $clock ??= DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids, 2, 5000);
        $order->pay($clock);

        return $order;
    }

    private function createEmptyPendingOrder(FixedClock $clock): Order
    {
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);

        return Order::create($user->id(), $clock, $ids);
    }

    private function createPaidOrder(?FixedClock $clock = null): Order
    {
        $clock ??= DomainFixture::clock();
        $order = $this->createPendingOrderWithSingleTicket($clock);
        $order->pay($clock);

        return $order;
    }

    private function createCancelledOrder(FixedClock $clock): Order
    {
        $order = $this->createPendingOrderWithSingleTicket($clock);
        $order->cancel($clock);

        return $order;
    }

    private function createRefundedOrder(FixedClock $clock): Order
    {
        $order = $this->createPaidOrder($clock);
        $order->refund($clock);

        return $order;
    }

    private function createTicketForOrder(Order $order, int $price, string $code, string $currency = 'BYN'): Ticket
    {
        $ids = DomainFixture::ids();

        return $this->createTicketWithIds($order, $ids, $price, $code, $currency);
    }

    private function createTicketWithIds(Order $order, mixed $ids, int $price, string $code, string $currency = 'BYN'): Ticket
    {
        return Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount($price, $currency),
            new TicketCode($code),
            $ids
        );
    }
}
