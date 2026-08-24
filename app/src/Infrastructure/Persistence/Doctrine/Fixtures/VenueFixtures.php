<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Dto\SeatData;
use App\Domain\Entity\Venue;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\SeatType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Messenger\MessageBusInterface;

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
        private readonly MessageBusInterface $commandBus,
        private readonly VenueRepositoryInterface $venues,
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

            $this->commandBus->dispatch(new CreateVenueCommand(
                name: $name,
                address: sprintf('%s, %s', $this->faker->streetName(), $this->faker->buildingNumber()),
                city: $this->faker->randomElement(['Minsk', 'Grodno', 'Brest', 'Vitebsk', 'Gomel']),
                latitude: $this->faker->latitude(53.8, 54.0),
                longitude: $this->faker->longitude(27.4, 27.7),
            ));

            $venue = $this->findByName($name);
            if ($venue === null) {
                continue;
            }

            $this->commandBus->dispatch(new AddSeatsToVenueCommand(
                venueId: $venue->id()->toRfc4122(),
                seats: $this->buildSeats(),
            ));

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

    /** @return SeatData[] */
    private function buildSeats(): array
    {
        $seats = [];

        foreach (['A', 'B', 'C', 'D'] as $row) {
            for ($number = 1; $number <= 12; ++$number) {
                $seats[] = new SeatData($row, $number, SeatType::Standard->value);
            }
        }

        for ($number = 1; $number <= 10; ++$number) {
            $seats[] = new SeatData('E', $number, SeatType::VIP->value, 'VIP zone');
        }

        for ($number = 1; $number <= 6; ++$number) {
            $seats[] = new SeatData('F', $number, SeatType::Premium->value, 'Fan zone');
        }

        return $seats;
    }
}
