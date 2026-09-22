<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\EventSeat;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\SeatStatus;
use App\Tests\Unit\Domain\Support\DomainFixture;
use App\Tests\Unit\Domain\Support\FixedClock;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use PHPUnit\Framework\TestCase;

final class EventSeatTest extends TestCase
{
    public function testCreateInitializesEventSeatAsFreeAndAvailable(): void
    {
        $seat = $this->createFreeEventSeat();

        self::assertSame(SeatStatus::Free, $seat->status());
        self::assertTrue($seat->isAvailable());
    }

    public function testCreateThrowsBusinessRuleViolationWhenSeatVenueDoesNotMatchEventVenue(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venueA = DomainFixture::venue($ids);
        $venueB = DomainFixture::venue($ids);
        $event = DomainFixture::event($venueA, $clock, $ids);
        $seat = DomainFixture::seat($venueB, $ids);

        $this->expectException(BusinessRuleViolationException::class);

        DomainFixture::eventSeat($event, $seat, $ids);
    }

    public function testReserveTransitionsFreeSeatToReserved(): void
    {
        $seat = $this->createFreeEventSeat();

        $seat->reserve();

        self::assertSame(SeatStatus::Reserved, $seat->status());
        self::assertFalse($seat->isAvailable());
    }

    public function testReserveThrowsBusinessRuleViolationWhenSeatIsAlreadyReserved(): void
    {
        $seat = $this->createReservedEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->reserve();
    }

    public function testReserveThrowsBusinessRuleViolationWhenSeatIsSold(): void
    {
        $seat = $this->createSoldEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->reserve();
    }

    public function testSellTransitionsReservedSeatToSold(): void
    {
        $seat = $this->createReservedEventSeat();

        $seat->sell();

        self::assertSame(SeatStatus::Sold, $seat->status());
    }

    public function testSellThrowsBusinessRuleViolationWhenSeatIsFree(): void
    {
        $seat = $this->createFreeEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->sell();
    }

    public function testSellThrowsBusinessRuleViolationWhenSeatIsAlreadySold(): void
    {
        $seat = $this->createSoldEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->sell();
    }

    public function testReleaseTransitionsReservedSeatToFree(): void
    {
        $seat = $this->createReservedEventSeat();

        $seat->release();

        self::assertSame(SeatStatus::Free, $seat->status());
        self::assertTrue($seat->isAvailable());
    }

    public function testReleaseThrowsBusinessRuleViolationWhenSeatIsFree(): void
    {
        $seat = $this->createFreeEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->release();
    }

    public function testReleaseThrowsBusinessRuleViolationWhenSeatIsSold(): void
    {
        $seat = $this->createSoldEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->release();
    }

    public function testUnsellTransitionsSoldSeatToFree(): void
    {
        $seat = $this->createSoldEventSeat();

        $seat->unsell();

        self::assertSame(SeatStatus::Free, $seat->status());
        self::assertTrue($seat->isAvailable());
    }

    public function testUnsellThrowsBusinessRuleViolationWhenSeatIsFree(): void
    {
        $seat = $this->createFreeEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->unsell();
    }

    public function testUnsellThrowsBusinessRuleViolationWhenSeatIsReserved(): void
    {
        $seat = $this->createReservedEventSeat();

        $this->expectException(BusinessRuleViolationException::class);

        $seat->unsell();
    }

    private function createFreeEventSeat(): EventSeat
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

        return DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );
    }

    private function createReservedEventSeat(): EventSeat
    {
        $seat = $this->createFreeEventSeat();
        $seat->reserve();

        return $seat;
    }

    private function createSoldEventSeat(): EventSeat
    {
        $seat = $this->createReservedEventSeat();
        $seat->sell();

        return $seat;
    }
}
