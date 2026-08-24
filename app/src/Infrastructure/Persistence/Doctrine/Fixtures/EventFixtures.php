<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Application\Command\Event\CreateEventCommand;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Dto\EventSeatData;
use App\Domain\Entity\Event;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\ValueObject\SeatType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Messenger\MessageBusInterface;

final class EventFixtures extends Fixture implements DependentFixtureInterface
{
    private const EVENT_COUNT = 8;

    private Generator $faker;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly EventRepositoryInterface $events,
        private readonly SeatRepositoryInterface $seats,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::EVENT_COUNT; ++$i) {
            $venueIndex = $i % 6;

            /** @var \App\Domain\Entity\Venue $venue */
            $venue = $this->getReference(sprintf('venue_%d', $venueIndex), \App\Domain\Entity\Venue::class);

            $title = sprintf('%s — %s', $this->eventKind($i), (string) $venue->name());

            if ($this->findByTitle($title) !== null) {
                $this->addReference(sprintf('event_%d', $i), $this->findByTitle($title));
                continue;
            }

            $basePrice = $this->faker->randomElement([2000, 2500, 3000, 3500, 4500, 5000]);

            $this->commandBus->dispatch(new CreateEventCommand(
                title: mb_substr($title, 0, 100),
                description: $this->faker->realText(300),
                date: new \DateTimeImmutable(sprintf('+%d days +%d:00', $this->faker->numberBetween(5, 120), $this->faker->numberBetween(16, 21))),
                venueId: $venue->id()->toRfc4122(),
                seats: $this->buildEventSeats($venue, $basePrice),
            ));

            $event = $this->findByTitle($title);
            if ($event === null) {
                continue;
            }

            // Keep one draft event to exercise the draft flow.
            if ($i !== self::EVENT_COUNT - 1) {
                $this->commandBus->dispatch(new PublishEventCommand($event->id()->toRfc4122()));
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

    /** @return EventSeatData[] */
    private function buildEventSeats(\App\Domain\Entity\Venue $venue, int $basePrice): array
    {
        $result = [];

        foreach ($this->seats->findByVenueId($venue->id()) as $seat) {
            $result[] = new EventSeatData(
                seatId: $seat->id()->toRfc4122(),
                priceAmount: $this->priceForType($seat->type(), $basePrice),
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
