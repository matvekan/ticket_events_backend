<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Venue;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\SeatType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

final class EventFixtures extends Fixture implements DependentFixtureInterface
{
    private const EVENT_COUNT = 8;

    private Generator $faker;

    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly VenueRepositoryInterface $venues,
        private readonly SeatRepositoryInterface $seats,
        private readonly EventSeatRepositoryInterface $eventSeats,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::EVENT_COUNT; ++$i) {
            $venueIndex = $i % 6;

            /** @var Venue $venue */
            $venue = $this->getReference(sprintf('venue_%d', $venueIndex), Venue::class);

            $title = sprintf('%s — %s', $this->eventKind($i), (string) $venue->name());

            if ($this->findByTitle($title) !== null) {
                $this->addReference(sprintf('event_%d', $i), $this->findByTitle($title));
                continue;
            }

            $basePrice = $this->faker->randomElement([2000, 2500, 3000, 3500, 4500, 5000]);

            $event = new Event(
                id: EventId::generate(),
                title: mb_substr($title, 0, 100),
                description: $this->faker->realText(300),
                date: new \DateTimeImmutable(sprintf('+%d days +%d:00', $this->faker->numberBetween(5, 120), $this->faker->numberBetween(16, 21))),
                venueId: $venue->id(),
                status: 'draft',
            );

            $this->events->save($event);
            $manager->flush();

            $eventSeats = $this->buildEventSeats($event, $venue, $basePrice);
            foreach ($eventSeats as $eventSeat) {
                $this->eventSeats->save($eventSeat);
            }
            $manager->flush();

            // Keep one draft event to exercise the draft flow.
            if ($i !== self::EVENT_COUNT - 1) {
                $event->publish();
                $this->events->save($event);
                $manager->flush();
            }

            $this->addReference(sprintf('event_%d', $i), $event);
        }
    }

    /** @return string[] */
    public function getDependencies(): array
    {
        return [VenueFixtures::class];
    }

    private function eventKind(int $index): string
    {
        $kinds = [
            'Rock Symphony Night',
            'Jazz Evening',
            'Electronic Music Festival',
            'Hockey All-Star Match',
            'Classical Piano Recital',
            'Stand Up Comedy Night',
            'String Quartet: Baroque',
            'New Year Family Musical',
        ];

        return $kinds[$index % count($kinds)];
    }

    private function findByTitle(string $title): ?Event
    {
        foreach ($this->events->findAll() as $event) {
            if ((string) $event->title() === $title) {
                return $event;
            }
        }

        return null;
    }

    /** @return EventSeat[] */
    private function buildEventSeats(Event $event, Venue $venue, int $basePrice): array
    {
        $result = [];

        foreach ($this->seats->findByVenueId($venue->id()) as $seat) {
            $result[] = new EventSeat(
                id: EventSeatId::generate(),
                eventId: $event->id(),
                seatId: $seat->id(),
                price: new Price(
                    amount: $this->priceForType($seat->type(), $basePrice),
                    currency: 'BYN',
                ),
                status: 'available',
            );
        }

        return $result;
    }

    private function priceForType(SeatType $type, int $basePrice): int
    {
        return match ($type) {
            SeatType::Standard => $basePrice,
            SeatType::VIP => (int) round($basePrice * 1.7),
            SeatType::Premium => (int) round($basePrice * 2.5),
        };
    }
}
