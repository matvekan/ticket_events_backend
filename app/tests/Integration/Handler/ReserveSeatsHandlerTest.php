<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatStatus;
use App\Domain\ValueObject\UserId;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Application layer: ReserveSeatsHandler + real PostgreSQL (_test) + real TransactionManager.
 * External APIs (Stripe, Elasticsearch) are not touched by this flow.
 */
final class ReserveSeatsHandlerTest extends KernelTestCase
{
    public function testReserveSeatsPersistsOrderAndReservesSeat(): void
    {
        // Arrange
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
        /** @var \App\Domain\Entity\User $user */
        $user = $data['user'];
        /** @var \App\Domain\Entity\EventSeat $eventSeat */
        $eventSeat = $data['eventSeat'];
        $handler = $container->get(ReserveSeatsHandler::class);

        // Act
        $handler(new ReserveSeatsCommand($user->rawId(), [$eventSeat->rawId()]));

        // Assert
        $em->clear();
        $orders = $container->get(OrderRepositoryInterface::class)->findByUserId(new UserId($user->rawId()));
        self::assertCount(1, $orders);
        self::assertInstanceOf(Order::class, $orders[0]);
        self::assertCount(1, $orders[0]->tickets());

        $seat = $em->getRepository(\App\Domain\Entity\EventSeat::class)->find($eventSeat->rawId());
        self::assertSame(SeatStatus::Reserved, $seat->status());
    }

    public function testReserveSeatsFailsForUnknownSeat(): void
    {
        // Arrange
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
        $handler = $container->get(ReserveSeatsHandler::class);

        // Act + Assert (one behavior: 404 for missing seats)
        $this->expectException(\App\Domain\Exception\EntityNotFoundException::class);
        $handler(new ReserveSeatsCommand($data['user']->rawId(), ['00000000-0000-4000-8000-000000000000']));
    }
}
