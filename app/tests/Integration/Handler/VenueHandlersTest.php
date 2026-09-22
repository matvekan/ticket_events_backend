<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\CommandHandler\Venue\CreateVenueHandler;
use App\Application\CommandHandler\Venue\AddSeatsHandler;
use App\Application\Dto\SeatData;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Application layer: CreateVenueHandler and AddSeatsHandler with real PostgreSQL.
 */
final class VenueHandlersTest extends KernelTestCase
{
    public function testCreateVenuePersistsVenueWithAllAttributes(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();

        $handler = $container->get(CreateVenueHandler::class);
        $handler(new CreateVenueCommand('Handler Venue ' . uniqid(), '123 Test St', 'Moscow', 55.75, 37.61));
        $em->clear();

        $venues = $container->get(VenueRepositoryInterface::class)->findAll();
        $venue = end($venues);

        self::assertNotNull($venue);
        self::assertSame('123 Test St', $venue->address()->toString());
        self::assertSame('Moscow', $venue->city()->toString());
        self::assertSame(55.75, $venue->latitude());
        self::assertSame(37.61, $venue->longitude());
    }

    public function testAddSeatsPersistsSeatsWithAllAttributes(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();

        $venueHandler = $container->get(CreateVenueHandler::class);
        $venueHandler(new CreateVenueCommand('Seat Handler Hall ' . uniqid(), '123 Test St', 'Moscow', null, null));
        $em->clear();
        $venues = $container->get(VenueRepositoryInterface::class)->findAll();
        $venue = end($venues);

        $handler = $container->get(AddSeatsHandler::class);
        $handler(new AddSeatsToVenueCommand($venue->rawId(), [
            new SeatData('A', 1, 'standard', 'Orchestra'),
            new SeatData('B', 5, 'vip', 'VIP'),
            new SeatData('C', 10, 'premium', 'Premium'),
        ]));
        $em->clear();

        $seats = $container->get(SeatRepositoryInterface::class)->findByVenueId($venue->id());

        self::assertCount(3, $seats);
        $types = array_map(fn ($s) => $s->type()->value, $seats);
        self::assertContains('standard', $types);
        self::assertContains('vip', $types);
        self::assertContains('premium', $types);
    }

    public function testAddSeatsFailsForUnknownVenue(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(AddSeatsHandler::class);

        $this->expectException(\App\Domain\Exception\EntityNotFoundException::class);

        $handler(new AddSeatsToVenueCommand('00000000-0000-4000-8000-000000000000', [
            new SeatData('A', 1, 'standard', 'Orchestra'),
        ]));
    }
}