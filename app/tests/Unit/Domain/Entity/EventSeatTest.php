<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\SeatStatus;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class EventSeatTest extends TestCase
{
    public function testReserveTransitionsFreeToReserved(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );

        // Act
        $seat->reserve();

        // Assert
        self::assertSame(SeatStatus::Reserved, $seat->status());
    }

    public function testReserveFailsWhenAlreadyReserved(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );
        $seat->reserve();

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $seat->reserve();
    }

    public function testSellTransitionsReservedToSold(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );
        $seat->reserve();

        // Act
        $seat->sell();

        // Assert
        self::assertSame(SeatStatus::Sold, $seat->status());
    }

    public function testSellFailsWhenFree(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $seat->sell();
    }

    public function testReleaseTransitionsReservedToFree(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );
        $seat->reserve();

        // Act
        $seat->release();

        // Assert
        self::assertTrue($seat->isAvailable());
    }

    public function testReleaseFailsWhenFree(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $seat->release();
    }

    public function testUnsellTransitionsSoldToFree(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $seat = DomainFixture::eventSeat(
            DomainFixture::event($venue, $clock, $ids),
            DomainFixture::seat($venue, $ids),
            $ids
        );
        $seat->reserve();
        $seat->sell();

        // Act
        $seat->unsell();

        // Assert
        self::assertTrue($seat->isAvailable());
    }
}
