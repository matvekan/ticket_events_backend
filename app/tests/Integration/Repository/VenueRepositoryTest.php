<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Command\Venue\AddSeatsCommand;
use App\Application\CommandHandler\Venue\AddSeatsHandler;
use App\Application\CommandHandler\Venue\CreateVenueHandler;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatType;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrineVenueRepository with real PostgreSQL.
 */
final class VenueRepositoryTest extends KernelTestCase
{
    public function testFindAllReturnsAllVenues(): void
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

        $venues = $container->get(VenueRepositoryInterface::class)->findAll();

        $found = false;
        foreach ($venues as $venue) {
            if ($venue->rawId() === $data['venue']->rawId()) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found);
    }

    public function testFindByIdReturnsVenueWithAllAttributes(): void
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

        $venue = $container->get(VenueRepositoryInterface::class)->findById($data['venue']->id());

        self::assertNotNull($venue);
        self::assertSame($data['venue']->rawId(), $venue->rawId());
        self::assertSame($data['venue']->name()->toString(), $venue->name()->toString());
        self::assertSame($data['venue']->address()->toString(), $venue->address()->toString());
        self::assertSame($data['venue']->city()->toString(), $venue->city()->toString());
    }

    public function testFindByIdReturnsNullForUnknownVenue(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $venue = $container->get(VenueRepositoryInterface::class)->findById(
            \App\Domain\ValueObject\VenueId::fromString('00000000-0000-4000-8000-000000000000')
        );

        self::assertNull($venue);
    }

    public function testSavePersistsVenueWithGeoCoordinates(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(IdGeneratorInterface::class);

        $handler = $container->get(CreateVenueHandler::class);
        $handler(new CreateVenueCommand('Geo Hall', '123 Test St', 'Moscow', 55.75, 37.61));
        $em->clear();

        $venues = $container->get(VenueRepositoryInterface::class)->findAll();
        $venue = end($venues);

        self::assertNotNull($venue->latitude());
        self::assertNotNull($venue->longitude());
        self::assertSame(55.75, $venue->latitude());
        self::assertSame(37.61, $venue->longitude());
    }
}