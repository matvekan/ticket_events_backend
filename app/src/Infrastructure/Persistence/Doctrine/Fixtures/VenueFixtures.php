<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\Seat;
use App\Domain\Entity\Venue;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use function sprintf;

final class VenueFixtures extends Fixture
{
    private const array VENUE_NAMES = [
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
        private readonly IdGeneratorInterface $ids,
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

            $city = $this->faker->randomElement(['Minsk', 'Grodno', 'Brest', 'Vitebsk', 'Gomel']);
            assert(is_string($city));

            $venue = Venue::create(
                new VenueName($name),
                new VenueAddress(sprintf('%s, %s', $this->faker->streetName(), $this->faker->buildingNumber())),
                new VenueCity($city),
                $this->ids,
                $this->faker->latitude(53.8, 54.0),
                $this->faker->longitude(27.4, 27.7),
            );

            $this->venues->save($venue);
            $manager->flush();

            $seats = $this->buildSeats($venue);

            $this->seats->saveAll($seats);
            $manager->flush();

            $this->addReference(sprintf('venue_%d', $i), $venue);
        }
    }

    private function findByName(string $name): ?Venue
    {
        return array_find(
            $this->venues->findAll(),
            static fn (Venue $venue): bool => (string) $venue->name() === $name,
        );
    }

    /**
     * @return array<int, Seat>
     */
    private function buildSeats(Venue $venue): array
    {
        $seats = [];

        foreach (['A', 'B', 'C', 'D'] as $row) {
            for ($number = 1; $number <= 12; ++$number) {
                $seats[] = Seat::create(
                    $venue->id(),
                    new SeatRow($row),
                    new SeatNumber($number),
                    SeatType::Standard,
                    $this->ids,
                );
            }
        }

        for ($number = 1; $number <= 10; ++$number) {
            $seats[] = Seat::create(
                $venue->id(),
                new SeatRow('E'),
                new SeatNumber($number),
                SeatType::VIP,
                $this->ids,
                new SeatSector('VIP zone'),
            );
        }

        for ($number = 1; $number <= 6; ++$number) {
            $seats[] = Seat::create(
                $venue->id(),
                new SeatRow('F'),
                new SeatNumber($number),
                SeatType::Premium,
                $this->ids,
                new SeatSector('Fan zone'),
            );
        }

        return $seats;
    }
}
