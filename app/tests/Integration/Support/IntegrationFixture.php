<?php

declare(strict_types=1);

namespace App\Tests\Integration\Support;

use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Seat;
use App\Domain\Entity\User;
use App\Domain\Entity\Venue;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;

final class IntegrationFixture
{
    /**
     * @return array{user: User, venue: Venue, seat: Seat, event: Event, eventSeat: EventSeat}
     */
    public static function createPublishedEventWithSeat(
        EntityManagerInterface $em,
        ClockInterface $clock,
        IdGeneratorInterface $ids,
        string $suffix,
    ): array {
        $user = User::create(
            new UserId($ids->generate()),
            new Name('Integration User'),
            new Email(sprintf('int_%s@example.com', $suffix)),
        );
        $em->persist($user);

        $venue = Venue::create(
            new VenueName(sprintf('Hall %s', substr($suffix, 0, 8))),
            new VenueAddress('123 Test Street'),
            new VenueCity('Moscow'),
            $ids,
        );
        $em->persist($venue);

        $seat = Seat::create(
            $venue->id(),
            new SeatRow('A'),
            new SeatNumber(1),
            SeatType::Standard,
            $ids,
        );
        $em->persist($seat);

        $event = Event::create(
            new EventTitle(sprintf('Concert %s', substr($suffix, 0, 8))),
            new EventDescription('An amazing test concert event for integration'),
            new \DateTimeImmutable('2026-12-01T20:00:00Z'),
            $venue,
            $clock,
            $ids,
        );
        $em->persist($event);

        $eventSeat = EventSeat::create($event, $seat, 5000, $ids);
        $em->persist($eventSeat);
        $em->flush();

        // Reload event so eventSeats collection is populated, then publish.
        $em->clear();
        $event = $em->getRepository(Event::class)->find($event->rawId());
        $event->publish($clock);
        $em->flush();
        $em->clear();

        $user = $em->getRepository(User::class)->find($user->rawId());
        $eventSeat = $em->getRepository(EventSeat::class)->find($eventSeat->rawId());
        $event = $em->getRepository(Event::class)->find($event->rawId());

        return ['user' => $user, 'venue' => $venue, 'seat' => $seat, 'event' => $event, 'eventSeat' => $eventSeat];
    }
}
