<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Order;
use App\Domain\Entity\Ticket;
use App\Domain\Event\OrderCancelledEvent;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Event\OrderRefundedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketCode;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testPayTransitionsPendingToPaid(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);

        // Act
        $order->pay($clock);

        // Assert
        self::assertSame(OrderStatus::Paid, $order->status());
        self::assertSame(5000, $order->totalPrice()->amount());
        $events = $order->releaseEvents();
        self::assertInstanceOf(OrderPaidEvent::class, $events[0]);
    }

    public function testPayFailsWhenAlreadyPaid(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $order->pay($clock);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $order->pay($clock);
    }

    public function testPayFailsWithoutTickets(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $order->pay($clock);
    }

    public function testCancelTransitionsPendingToCancelled(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);

        // Act
        $order->cancel($clock);

        // Assert
        self::assertSame(OrderStatus::Cancelled, $order->status());
        $events = $order->releaseEvents();
        self::assertInstanceOf(OrderCancelledEvent::class, $events[0]);
    }

    public function testCancelFailsWhenPaid(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $order->pay($clock);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $order->cancel($clock);
    }

    public function testRefundTransitionsPaidToRefunded(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $order->pay($clock);

        // Act
        $order->refund($clock);

        // Assert
        self::assertSame(OrderStatus::Refunded, $order->status());
        $events = $order->releaseEvents();
        // pay + refund events recorded
        self::assertInstanceOf(OrderRefundedEvent::class, $events[1]);
    }

    public function testRefundFailsWhenPending(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $order->refund($clock);
    }

    public function testAddTicketIgnoresDuplicateId(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);
        $ticket = Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(1000),
            new TicketCode('TKT-ABC12345'),
            $ids
        );
        $order->addTicket($ticket);

        // Act (same ticket id added twice — second add is a no-op)
        $order->addTicket($ticket);

        // Assert
        self::assertCount(1, $order->tickets());
        self::assertSame(1000, $order->totalPrice()->amount());
    }

    public function testAddTicketRejectsMixedCurrencies(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);
        $order->addTicket(Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(1000, 'BYN'),
            new TicketCode('TKT-ABC12345'),
            $ids
        ));

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $order->addTicket(Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(1000, 'USD'),
            new TicketCode('TKT-DEF67890'),
            $ids
        ));
    }

    public function testAddTicketRecalculatesTotal(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);

        // Act
        $order->addTicket(Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(2000),
            new TicketCode('TKT-ABC12345'),
            $ids
        ));
        $order->addTicket(Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(3000),
            new TicketCode('TKT-DEF67890'),
            $ids
        ));

        // Assert
        self::assertSame(5000, $order->totalPrice()->amount());
    }
}
