<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Order;
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
    public function testCreateInitializesTicketAsReservedWithCorrectPriceAndCode(): void
    {
        $ticket = $this->createReservedTicket();

        self::assertSame(TicketStatus::Reserved, $ticket->status());
        self::assertSame(1500, $ticket->price()->amount());
    }

    public function testActivateTransitionsReservedTicketToActive(): void
    {
        $ticket = $this->createReservedTicket();

        $ticket->activate();

        self::assertSame(TicketStatus::Active, $ticket->status());
    }

    public function testActivateThrowsBusinessRuleViolationWhenTicketIsAlreadyActive(): void
    {
        $ticket = $this->createActiveTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->activate();
    }

    public function testActivateThrowsBusinessRuleViolationWhenTicketIsCancelled(): void
    {
        $ticket = $this->createCancelledTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->activate();
    }

    public function testScanTransitionsActiveTicketToUsed(): void
    {
        $ticket = $this->createActiveTicket();

        $ticket->scan();

        self::assertSame(TicketStatus::Used, $ticket->status());
    }

    public function testScanThrowsBusinessRuleViolationWhenTicketIsReserved(): void
    {
        $ticket = $this->createReservedTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->scan();
    }

    public function testScanThrowsBusinessRuleViolationWhenTicketIsAlreadyUsed(): void
    {
        $ticket = $this->createUsedTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->scan();
    }

    public function testScanThrowsBusinessRuleViolationWhenTicketIsRefunded(): void
    {
        $ticket = $this->createRefundedTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->scan();
    }

    public function testRefundTransitionsActiveTicketToRefunded(): void
    {
        $ticket = $this->createActiveTicket();

        $ticket->refund();

        self::assertSame(TicketStatus::Refunded, $ticket->status());
    }

    public function testRefundTransitionsUsedTicketToRefunded(): void
    {
        $ticket = $this->createUsedTicket();

        $ticket->refund();

        self::assertSame(TicketStatus::Refunded, $ticket->status());
    }

    public function testRefundThrowsBusinessRuleViolationWhenTicketIsReserved(): void
    {
        $ticket = $this->createReservedTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->refund();
    }

    public function testRefundThrowsBusinessRuleViolationWhenTicketIsAlreadyRefunded(): void
    {
        $ticket = $this->createRefundedTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->refund();
    }

    public function testCancelTransitionsReservedTicketToCancelled(): void
    {
        $ticket = $this->createReservedTicket();

        $ticket->cancel();

        self::assertSame(TicketStatus::Cancelled, $ticket->status());
    }

    public function testCancelTransitionsActiveTicketToCancelled(): void
    {
        $ticket = $this->createActiveTicket();

        $ticket->cancel();

        self::assertSame(TicketStatus::Cancelled, $ticket->status());
    }

    public function testCancelThrowsBusinessRuleViolationWhenTicketIsUsed(): void
    {
        $ticket = $this->createUsedTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->cancel();
    }

    public function testCancelThrowsBusinessRuleViolationWhenTicketIsAlreadyCancelled(): void
    {
        $ticket = $this->createCancelledTicket();

        $this->expectException(BusinessRuleViolationException::class);

        $ticket->cancel();
    }

    private function createReservedTicket(): Ticket
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $order = Order::create($user->id(), $clock, $ids);

        return Ticket::create(
            $order,
            new EventSeatId($ids->generate()),
            Price::fromAmount(1500),
            new TicketCode('TKT-ZZZZ9999'),
            $ids
        );
    }

    private function createActiveTicket(): Ticket
    {
        $ticket = $this->createReservedTicket();
        $ticket->activate();

        return $ticket;
    }

    private function createUsedTicket(): Ticket
    {
        $ticket = $this->createActiveTicket();
        $ticket->scan();

        return $ticket;
    }

    private function createRefundedTicket(): Ticket
    {
        $ticket = $this->createActiveTicket();
        $ticket->refund();

        return $ticket;
    }

    private function createCancelledTicket(): Ticket
    {
        $ticket = $this->createReservedTicket();
        $ticket->cancel();

        return $ticket;
    }
}
