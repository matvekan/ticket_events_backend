<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Event;
use App\Domain\Event\EventCancelledEvent;
use App\Domain\Event\EventCreatedEvent;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\EventTitle;
use App\Tests\Unit\Domain\Support\DomainFixture;
use App\Tests\Unit\Domain\Support\FixedClock;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testCreatePersistsDraftEventAndRecordsEventCreatedEvent(): void
    {
        $clock = DomainFixture::clock();
        $venue = DomainFixture::venue(DomainFixture::ids());

        $event = $this->createDraftEvent($venue, $clock, DomainFixture::ids());

        self::assertSame(EventStatus::Draft, $event->status());
        self::assertInstanceOf(EventCreatedEvent::class, $event->releaseEvents()[0]);
    }

    public function testCreateThrowsBusinessRuleViolationWhenEventDateIsInThePast(): void
    {
        $clock = DomainFixture::clock('2026-06-01T12:00:00Z');
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);

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

    public function testPublishTransitionsDraftEventToPublishedAndRecordsStatusChangedEvent(): void
    {
        $clock = DomainFixture::clock();
        $event = $this->createPublishedReadyEvent($clock);

        $event->publish($clock);

        self::assertSame(EventStatus::Published, $event->status());
        self::assertInstanceOf(EventStatusChangedEvent::class, $event->releaseEvents()[1]);
    }

    public function testPublishThrowsBusinessRuleViolationWhenEventHasNoSeats(): void
    {
        $clock = DomainFixture::clock();
        $event = DomainFixture::event(DomainFixture::venue(DomainFixture::ids()), $clock, DomainFixture::ids());

        $this->expectException(BusinessRuleViolationException::class);

        $event->publish($clock);
    }

    public function testPublishThrowsBusinessRuleViolationWhenEventIsAlreadyPublished(): void
    {
        $clock = DomainFixture::clock();
        $event = $this->createPublishedEvent($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $event->publish($clock);
    }

    public function testPublishThrowsBusinessRuleViolationWhenEventDateIsInThePastAtPublishTime(): void
    {
        $pastClock = DomainFixture::clock('2026-12-02T12:00:00Z');
        $event = $this->createPublishedReadyEvent(DomainFixture::clock());

        $this->expectException(BusinessRuleViolationException::class);

        $event->publish($pastClock);
    }

    public function testEnsureCanBeBookedThrowsBusinessRuleViolationWhenEventIsDraft(): void
    {
        $clock = DomainFixture::clock();
        $event = DomainFixture::event(DomainFixture::venue(DomainFixture::ids()), $clock, DomainFixture::ids());

        $this->expectException(BusinessRuleViolationException::class);

        $event->ensureCanBeBooked($clock);
    }

    public function testEnsureCanBeBookedThrowsBusinessRuleViolationWhenEventDateIsInThePast(): void
    {
        $clock = DomainFixture::clock('2026-12-02T12:00:00Z');
        $event = $this->createPublishedEvent(DomainFixture::clock());

        $this->expectException(BusinessRuleViolationException::class);

        $event->ensureCanBeBooked($clock);
    }

    public function testEnsureCanBeBookedDoesNotThrowWhenEventIsPublishedAndInFuture(): void
    {
        $clock = DomainFixture::clock();
        $event = $this->createPublishedEvent($clock);

        $event->ensureCanBeBooked($clock);

        self::assertSame(EventStatus::Published, $event->status());
    }

    public function testCancelTransitionsPublishedEventToCancelledAndRecordsTwoEvents(): void
    {
        $clock = DomainFixture::clock();
        $event = $this->createPublishedEvent($clock);

        $event->cancel($clock);

        self::assertSame(EventStatus::Cancelled, $event->status());
        $events = $event->releaseEvents();
        self::assertInstanceOf(EventStatusChangedEvent::class, $events[2]);
        self::assertInstanceOf(EventCancelledEvent::class, $events[3]);
    }

    public function testCancelTransitionsDraftEventToCancelled(): void
    {
        $clock = DomainFixture::clock();
        $event = DomainFixture::event(DomainFixture::venue(DomainFixture::ids()), $clock, DomainFixture::ids());

        $event->cancel($clock);

        self::assertSame(EventStatus::Cancelled, $event->status());
    }

    public function testCancelThrowsBusinessRuleViolationWhenEventIsAlreadyCancelled(): void
    {
        $clock = DomainFixture::clock();
        $event = DomainFixture::event(DomainFixture::venue(DomainFixture::ids()), $clock, DomainFixture::ids());
        $event->cancel($clock);

        $this->expectException(BusinessRuleViolationException::class);

        $event->cancel($clock);
    }

    public function testEventSeatsAreReturnedAsArrayAfterPublish(): void
    {
        $clock = DomainFixture::clock();
        $event = $this->createPublishedEvent($clock);

        self::assertCount(1, $event->eventSeats());
    }

    private function createDraftEvent(mixed $venue, FixedClock $clock, FixedIdGenerator $ids): Event
    {
        return Event::create(
            new EventTitle('Test Concert Title'),
            new EventDescription('An amazing test concert event description'),
            new \DateTimeImmutable('2026-12-01T20:00:00Z'),
            $venue,
            $clock,
            $ids
        );
    }

    private function createPublishedReadyEvent(FixedClock $clock): Event
    {
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seat = DomainFixture::seat($venue, $ids);
        $this->attachSeatsToEvent($event, [DomainFixture::eventSeat($event, $seat, $ids)]);

        return $event;
    }

    private function createPublishedEvent(FixedClock $clock): Event
    {
        $event = $this->createPublishedReadyEvent($clock);
        $event->publish($clock);

        return $event;
    }

    private function attachSeatsToEvent(Event $event, array $seats): void
    {
        $property = new \ReflectionProperty(Event::class, 'eventSeats');
        $property->setValue($event, $seats);
    }
}
