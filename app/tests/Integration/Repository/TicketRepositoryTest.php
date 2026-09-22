<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Event\CreateEventCommand;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\CommandHandler\Event\CreateEventHandler;
use App\Application\CommandHandler\Event\PublishEventHandler;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\CommandHandler\Venue\CreateVenueHandler;
use App\Domain\Entity\Ticket;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\TicketStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrineTicketRepository with real PostgreSQL.
 */
final class TicketRepositoryTest extends KernelTestCase
{
    public function testFindByOrderIdReturnsAllTicketsForOrder(): void
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
        $container->get(ReserveSeatsHandler::class)(new ReserveSeatsCommand($data['user']->rawId(), [$data['eventSeat']->rawId()]));
        $em->clear();

        $orders = $container->get(OrderRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($data['user']->rawId())
        );
        $order = $orders[0];

        $tickets = $container->get(TicketRepositoryInterface::class)->findByOrderId($order->id());

        self::assertCount(1, $tickets);
        self::assertSame(TicketStatus::Reserved, $tickets[0]->status());
        self::assertSame($data['eventSeat']->rawId(), $tickets[0]->eventSeatId()->toString());
    }

    public function testFindByOrderIdReturnsEmptyForUnknownOrder(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $tickets = $container->get(TicketRepositoryInterface::class)->findByOrderId(
            \App\Domain\ValueObject\OrderId::fromString('00000000-0000-4000-8000-000000000000')
        );

        self::assertSame([], $tickets);
    }

    public function testFindByCodeReturnsTicketWhenExists(): void
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
        $container->get(ReserveSeatsHandler::class)(new ReserveSeatsCommand($data['user']->rawId(), [$data['eventSeat']->rawId()]));
        $em->clear();

        $orders = $container->get(OrderRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($data['user']->rawId())
        );
        $order = $orders[0];

        $tickets = $container->get(TicketRepositoryInterface::class)->findByOrderId($order->id());
        $ticket = $tickets[0];

        $found = $container->get(TicketRepositoryInterface::class)->findByCode($ticket->code());

        self::assertNotNull($found);
        self::assertSame($ticket->rawId(), $found->rawId());
    }
}