<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\Seat;
use App\Domain\Entity\Venue;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\VenueId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

final class VenueFixtures extends Fixture
{
    /** Structural name templates (deterministic across runs, so fixtures are idempotent). */
    private const VENUE_NAMES = [
        'Grand Concert Hall',
        'City Sports Arena',
        'Downtown Event Palace',
        'Riverside Amphitheater',
        'Northside Expo Center',
        'Old Town Music Club',
    ];

    private Generator $faker;

    public function __construct(
        private readonly VenueRepositoryInterface $venues,
        private readonly SeatRepositoryInterface $seats,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::VENUE_NAMES as $i => $name) {
            $venue = $this->findByName($name);
            if ($venue !== null) {
                $this->addReference(sprintf('venue_%d', $i), $venue);
                continue;
            }

            $venue = new Venue(
                id: VenueId::generate(),
                name: $name,
                address: sprintf('%s, %s', $this->faker->streetName(), $this->faker->buildingNumber()),
                city: $this->faker->randomElement(['Minsk', 'Grodno', 'Brest', 'Vitebsk', 'Gomel']),
                latitude: $this->faker->latitude(53.8, 54.0),
                longitude: $this->faker->longitude(27.4, 27.7),
            );

            $this->venues->save($venue);
            $manager->flush();

            $seats = $this->buildSeats($venue);
            foreach ($seats as $seat) {
                $this->seats->save($seat);
            }
            $manager->flush();

            $this->addReference(sprintf('venue_%d', $i), $venue);
        }
    }

    private function findByName(string $name): ?Venue
    {
        foreach ($this->venues->findAll() as $venue) {
            if ((string) $venue->name() === $name) {
                return $venue;
            }
        }

        return null;
    }

    /** @return Seat[] */
    private function buildSeats(Venue $venue): array
    {
        $seats = [];

        foreach (['A', 'B', 'C', 'D'] as $row) {
            for ($number = 1; $number <= 12; ++$number) {
                $seats[] = new Seat(
                    id: SeatId::generate(),
                    venueId: $venue->id(),
                    row: $row,
                    number: $number,
                    type: SeatType::Standard,
                );
            }
        }

        for ($number = 1; $number <= 10; ++$number) {
            $seats[] = new Seat(
                id: SeatId::generate(),
                venueId: $venue->id(),
                row: 'E',
                number: $number,
                type: SeatType::VIP,
                zone: 'VIP zone',
            );
        }

        for ($number = 1; $number <= 6; ++$number) {
            $seats[] = new Seat(
                id: SeatId::generate(),
                venueId: $venue->id(),
                row: 'F',
                number: $number,
                type: SeatType::Premium,
                zone: 'Fan zone',
            );
        }

        return $seats;
    }
}
