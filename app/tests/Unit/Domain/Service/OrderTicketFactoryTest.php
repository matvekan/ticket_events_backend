<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Service\Order\OrderTicketFactory;
use App\Domain\Service\Order\TicketCodeGenerator;
use App\Domain\ValueObject\SeatStatus;
use App\Domain\ValueObject\TicketStatus;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class OrderTicketFactoryTest extends TestCase
{
    public function testCreateReservesSeatsAndCreatesOrderWithTickets(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seats = $this->createEventSeats($event, $venue, $ids, 2);
        $user = DomainFixture::user($ids);

        $order = $this->createFactory()->create($user->id(), $seats, $clock, $ids);

        self::assertCount(2, $order->tickets());
        self::assertSame(TicketStatus::Reserved, $order->tickets()[0]->status());
        self::assertSame(SeatStatus::Reserved, $seats[0]->status());
    }

    public function testCreateCalculatesTotalPriceFromSeatPrices(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seats = $this->createEventSeats($event, $venue, $ids, 2, 5000);
        $user = DomainFixture::user($ids);

        $order = $this->createFactory()->create($user->id(), $seats, $clock, $ids);

        self::assertSame(10000, $order->totalPrice()->amount());
    }

    public function testCreateRecordsSeatsReservedEventOnOrder(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seats = $this->createEventSeats($event, $venue, $ids, 1);
        $user = DomainFixture::user($ids);

        $order = $this->createFactory()->create($user->id(), $seats, $clock, $ids);

        self::assertNotEmpty($order->releaseEvents());
    }

    public function testCreateThrowsBusinessRuleViolationWhenSeatIsAlreadyReserved(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seats = $this->createEventSeats($event, $venue, $ids, 1);
        $seats[0]->reserve();
        $user = DomainFixture::user($ids);

        $this->expectException(\App\Domain\Exception\BusinessRuleViolationException::class);

        $this->createFactory()->create($user->id(), $seats, $clock, $ids);
    }

    private function createEventSeats(mixed $event, mixed $venue, mixed $ids, int $count, int $price = 5000): array
    {
        $seats = [];

        for ($i = 1; $i <= $count; ++$i) {
            $seats[] = DomainFixture::eventSeat($event, DomainFixture::seat($venue, $ids, 'A', $i), $ids, $price);
        }

        return $seats;
    }

    private function createFactory(): OrderTicketFactory
    {
        return new OrderTicketFactory(new TicketCodeGenerator());
    }
}
