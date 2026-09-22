<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Seat;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class SeatTest extends TestCase
{
    public function testCreateBindsSeatToVenueRowNumberAndType(): void
    {
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

        $seat = DomainFixture::seat($venue, $ids, 'B', 12);

        self::assertTrue($seat->venueId()->equals($venue->id()));
        self::assertSame('B', $seat->row()->toString());
        self::assertSame(12, $seat->number()->toInt());
        self::assertSame(SeatType::Standard, $seat->type());
    }

    public function testCreateLeavesSectorEmptyWhenSectorIsNotProvided(): void
    {
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

        $seat = DomainFixture::seat($venue, $ids);

        self::assertNull($seat->sector());
    }

    public function testCreateStoresSectorWhenSectorIsProvided(): void
    {
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

        $seat = $this->createSectorSeat($venue, $ids);

        self::assertSame('Orchestra', $seat->sector()->toString());
    }

    public function testSeatTypeExposesAllAllowedBusinessTypes(): void
    {
        $types = SeatType::validTypes();

        self::assertSame(['standard', 'vip', 'premium'], $types);
    }

    public function testCreateSupportsVipTypeForPremiumSeating(): void
    {
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

        $seat = $this->createTypedSeat($venue, $ids, SeatType::VIP);

        self::assertSame(SeatType::VIP, $seat->type());
    }

    private function createSectorSeat(\App\Domain\Entity\Venue $venue, \App\Tests\Unit\Domain\Support\FixedIdGenerator $ids): Seat
    {
        return Seat::create(
            $venue->id(),
            new SeatRow('A'),
            new SeatNumber(1),
            SeatType::Standard,
            $ids,
            new SeatSector('Orchestra'),
        );
    }

    private function createTypedSeat(\App\Domain\Entity\Venue $venue, \App\Tests\Unit\Domain\Support\FixedIdGenerator $ids, SeatType $type): Seat
    {
        return Seat::create($venue->id(), new SeatRow('A'), new SeatNumber(1), $type, $ids);
    }
}
