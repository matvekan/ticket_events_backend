<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Ticket;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\TicketStatus;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    public function testActivateTransitionsReservedToActive(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $ticket = $order->tickets()[0];

        // Act
        $ticket->activate();

        // Assert
        self::assertSame(TicketStatus::Active, $ticket->status());
    }

    public function testScanTransitionsActiveToUsed(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $ticket = $order->tickets()[0];
        $ticket->activate();

        // Act
        $ticket->scan();

        // Assert
        self::assertSame(TicketStatus::Used, $ticket->status());
    }

    public function testScanFailsWhenReserved(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $ticket = $order->tickets()[0];

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $ticket->scan();
    }

    public function testRefundTransitionsActiveToRefunded(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $ticket = $order->tickets()[0];
        $ticket->activate();

        // Act
        $ticket->refund();

        // Assert
        self::assertSame(TicketStatus::Refunded, $ticket->status());
    }

    public function testCancelTransitionsReservedToCancelled(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids);
        $ticket = $order->tickets()[0];

        // Act
        $ticket->cancel();

        // Assert
        self::assertSame(TicketStatus::Cancelled, $ticket->status());
    }

    public function testCreateStartsAsReserved(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = DomainFixture::orderWithTickets($user, $clock, $ids, 0);
        self::assertCount(0, $order->tickets());

        // Act
        $ticket = Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(1500),
            new TicketCode('TKT-ZZZZ9999'),
            $ids
        );

        // Assert
        self::assertSame(TicketStatus::Reserved, $ticket->status());
    }
}
