<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\CommandHandler\Venue\AddSeatsHandler;
use App\Application\CommandHandler\Venue\CreateVenueHandler;
use App\Application\Dto\SeatData;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatType;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrineSeatRepository with real PostgreSQL.
 */
final class SeatRepositoryTest extends KernelTestCase
{
    public function testFindByVenueIdReturnsAllSeatsForVenue(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(ClockInterface::class),
            $container->get(IdGeneratorInterface::class),
            $suffix,
        );

        $seats = $container->get(SeatRepositoryInterface::class)->findByVenueId($data['venue']->id());

        self::assertNotEmpty($seats);
        foreach ($seats as $seat) {
            self::assertTrue($seat->venueId()->equals($data['venue']->id()));
        }
    }

    public function testFindByVenueIdReturnsEmptyForUnknownVenue(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $seats = $container->get(SeatRepositoryInterface::class)->findByVenueId(
            \App\Domain\ValueObject\VenueId::fromString('00000000-0000-4000-8000-000000000000')
        );

        self::assertSame([], $seats);
    }

    public function testSavePersistsSeatsWithAllAttributes(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(IdGeneratorInterface::class);
        $clock = $container->get(ClockInterface::class);
        $suffix = uniqid();

        $venueHandler = $container->get(CreateVenueHandler::class);
        $venueHandler(new CreateVenueCommand('Seat Repo Hall ' . uniqid(), '123 Test St', 'Moscow', null, null));
        $em->clear();
        $venues = $container->get(VenueRepositoryInterface::class)->findAll();
        $venue = end($venues);

        $container->get(AddSeatsHandler::class)(new AddSeatsToVenueCommand($venue->rawId(), [
            new SeatData('A', 1, 'standard', 'Orchestra'),
            new SeatData('B', 5, 'vip', 'VIP'),
        ]));
        $em->clear();

        $seats = $container->get(SeatRepositoryInterface::class)->findByVenueId($venue->id());

        self::assertCount(2, $seats);
        $types = array_map(fn ($s) => $s->type()->value, $seats);
        self::assertContains('standard', $types);
        self::assertContains('vip', $types);
    }
}