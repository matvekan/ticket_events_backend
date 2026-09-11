<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Support;

use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\Seat;
use App\Domain\Entity\Ticket;
use App\Domain\Entity\User;
use App\Domain\Entity\Venue;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;

final class DomainFixture
{
    public static function clock(string $now = '2026-06-01T12:00:00Z'): FixedClock
    {
        return new FixedClock(new \DateTimeImmutable($now));
    }

    public static function ids(int $from = 1, int $count = 20): FixedIdGenerator
    {
        $ids = [];
        for ($i = 0; $i < $count; ++$i) {
            $ids[] = FixedIdGenerator::uuid($from + $i);
        }

        return new FixedIdGenerator($ids);
    }

    public static function venue(FixedIdGenerator $ids): Venue
    {
        return Venue::create(
            new VenueName('Main Hall'),
            new VenueAddress('123 Test Street'),
            new VenueCity('Moscow'),
            $ids
        );
    }

    public static function seat(Venue $venue, FixedIdGenerator $ids, string $row = 'A', int $number = 1): Seat
    {
        return Seat::create(
            $venue->id(),
            new SeatRow($row),
            new SeatNumber($number),
            SeatType::Standard,
            $ids
        );
    }

    public static function event(Venue $venue, FixedClock $clock, FixedIdGenerator $ids, string $date = '2026-12-01T20:00:00Z'): Event
    {
        return Event::create(
            new EventTitle('Test Concert Title'),
            new EventDescription('An amazing test concert event description'),
            new \DateTimeImmutable($date),
            $venue,
            $clock,
            $ids
        );
    }

    public static function eventSeat(Event $event, Seat $seat, FixedIdGenerator $ids, int $price = 5000): EventSeat
    {
        return EventSeat::create($event, $seat, $price, $ids);
    }

    public static function user(FixedIdGenerator $ids, string $email = 'user@example.com'): User
    {
        return User::create(
            new UserId($ids->generate()),
            new Name('Test User'),
            new Email($email)
        );
    }

    public static function orderWithTickets(User $user, FixedClock $clock, FixedIdGenerator $ids, int $ticketCount = 1, int $price = 5000): Order
    {
        // Arrange helper: creates order with N tickets (prices in BYN).
        $order = Order::create($user->id(), $clock, $ids);
        for ($i = 0; $i < $ticketCount; ++$i) {
            $ticket = Ticket::create(
                $order,
                new EventSeatId($ids->generate()),
                Price::fromAmount($price),
                new TicketCode(sprintf('TKT-%08d', 10000000 + $i)),
                $ids
            );
            $order->addTicket($ticket);
        }

        return $order;
    }
}
