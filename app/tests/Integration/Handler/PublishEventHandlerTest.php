<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Event\PublishEventCommand;
use App\Application\CommandHandler\Event\PublishEventHandler;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Entity\Seat;
use App\Domain\Entity\User;
use App\Domain\Entity\Venue;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PublishEventHandlerTest extends KernelTestCase
{
    public function testPublishEventTransitionsDraftEventToPublishedAndWritesOutboxEvent(): void
    {
        $context = $this->createDraftEventWithSeat();
        $handler = $context['container']->get(PublishEventHandler::class);

        $handler(new PublishEventCommand($context['eventId']));

        $this->assertEventIsPublished($context['eventId']);
        $this->assertPublishedOutboxEventExists($context['eventId']);
    }

    public function testPublishEventThrowsEntityNotFoundWhenEventDoesNotExist(): void
    {
        self::bootKernel();
        $handler = static::getContainer()->get(PublishEventHandler::class);

        $this->expectException(EntityNotFoundException::class);

        $handler(new PublishEventCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function testPublishEventThrowsBusinessRuleViolationWhenEventHasNoSeats(): void
    {
        $context = $this->createDraftEventWithoutSeats();
        $handler = $context['container']->get(PublishEventHandler::class);

        $this->expectException(\App\Domain\Exception\BusinessRuleViolationException::class);

        $handler(new PublishEventCommand($context['eventId']));
    }

    private function createDraftEventWithSeat(): array
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(IdGeneratorInterface::class);
        $clock = $container->get(ClockInterface::class);
        $suffix = uniqid();

        $venue = $this->persistVenue($em, $ids, $suffix);
        $seat = $this->persistSeat($em, $venue, $ids);
        $event = $this->persistDraftEvent($em, $venue, $clock, $ids, $suffix);
        $this->persistEventSeat($em, $event, $seat, $ids);

        $em->flush();
        $em->clear();

        return ['container' => $container, 'eventId' => $event->rawId()];
    }

    private function createDraftEventWithoutSeats(): array
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(IdGeneratorInterface::class);
        $clock = $container->get(ClockInterface::class);
        $suffix = uniqid();

        $venue = $this->persistVenue($em, $ids, $suffix);
        $event = $this->persistDraftEvent($em, $venue, $clock, $ids, $suffix);

        $em->flush();
        $em->clear();

        return ['container' => $container, 'eventId' => $event->rawId()];
    }

    private function persistVenue(EntityManagerInterface $em, IdGeneratorInterface $ids, string $suffix): Venue
    {
        $venue = Venue::create(
            new VenueName(sprintf('Pub Hall %s', substr($suffix, 0, 8))),
            new VenueAddress('123 Test Street'),
            new VenueCity('Moscow'),
            $ids
        );
        $em->persist($venue);

        return $venue;
    }

    private function persistSeat(EntityManagerInterface $em, Venue $venue, IdGeneratorInterface $ids): Seat
    {
        $seat = Seat::create($venue->id(), new SeatRow('A'), new SeatNumber(1), SeatType::Standard, $ids);
        $em->persist($seat);

        return $seat;
    }

    private function persistDraftEvent(EntityManagerInterface $em, Venue $venue, ClockInterface $clock, IdGeneratorInterface $ids, string $suffix): Event
    {
        $event = Event::create(
            new EventTitle(sprintf('Concert %s', substr($suffix, 0, 8))),
            new EventDescription('An amazing test concert event for integration'),
            new \DateTimeImmutable('2026-12-01T20:00:00Z'),
            $venue,
            $clock,
            $ids
        );
        $em->persist($event);

        return $event;
    }

    private function persistEventSeat(EntityManagerInterface $em, Event $event, Seat $seat, IdGeneratorInterface $ids): EventSeat
    {
        $eventSeat = EventSeat::create($event, $seat, 5000, $ids);
        $em->persist($eventSeat);

        return $eventSeat;
    }

    private function assertEventIsPublished(string $eventId): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $published = static::getContainer()->get(EventRepositoryInterface::class)->findById(EventId::fromString($eventId));

        self::assertNotNull($published);
        self::assertSame(EventStatus::Published, $published->status());
    }

    private function assertPublishedOutboxEventExists(string $eventId): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $outbox = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => EventStatusChangedEvent::class]);

        self::assertTrue($this->containsPublishedEvent($outbox, $eventId));
    }

    private function containsPublishedEvent(array $outbox, string $eventId): bool
    {
        foreach ($outbox as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof EventStatusChangedEvent && $event->eventId() === $eventId && $event->newStatus() === 'published') {
                return true;
            }
        }

        return false;
    }
}
