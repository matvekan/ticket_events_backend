<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Event;
use App\Domain\Event\EventCancelledEvent;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\EventTitle;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class EventTest extends TestCase
{
    public function testCreateFailsWhenDateInPast(): void
    {
        // Arrange
        $clock = DomainFixture::clock('2026-06-01T12:00:00Z');
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        Event::create(
            new EventTitle('Past Event Title'),
            new EventDescription('Description long enough for validation'),
            new \DateTimeImmutable('2026-01-01T12:00:00Z'),
            $venue,
            $clock,
            $ids
        );
    }

    public function testPublishFailsWithoutSeats(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $event = DomainFixture::event(DomainFixture::venue($ids), $clock, $ids);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $event->publish($clock);
    }

    public function testPublishTransitionsDraftToPublished(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        // EventSeat list is private without adder — inject one seat via reflection (domain invariant: publish requires seats).
        $seat = DomainFixture::seat($venue, $ids);
        $eventSeat = DomainFixture::eventSeat($event, $seat, $ids);
        $prop = new ReflectionProperty(Event::class, 'eventSeats');
        $prop->setValue($event, [$eventSeat]);

        // Act
        $event->publish($clock);

        // Assert
        self::assertSame(EventStatus::Published, $event->status());
        $events = $event->releaseEvents();
        self::assertInstanceOf(EventStatusChangedEvent::class, $events[1]);
    }

    public function testPublishFailsWhenAlreadyPublished(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seat = DomainFixture::seat($venue, $ids);
        $prop = new ReflectionProperty(Event::class, 'eventSeats');
        $prop->setValue($event, [DomainFixture::eventSeat($event, $seat, $ids)]);
        $event->publish($clock);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $event->publish($clock);
    }

    public function testEnsureCanBeBookedFailsWhenDraft(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $event = DomainFixture::event(DomainFixture::venue($ids), $clock, $ids);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $event->ensureCanBeBooked($clock);
    }

    public function testCancelTransitionsToCancelled(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $event = DomainFixture::event(DomainFixture::venue($ids), $clock, $ids);

        // Act
        $event->cancel($clock);

        // Assert
        self::assertSame(EventStatus::Cancelled, $event->status());
        $events = $event->releaseEvents();
        self::assertInstanceOf(EventCancelledEvent::class, $events[2]);
    }

    public function testCancelFailsWhenAlreadyCancelled(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $event = DomainFixture::event(DomainFixture::venue($ids), $clock, $ids);
        $event->cancel($clock);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $event->cancel($clock);
    }
}
