<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\MessageHandler\ExpirePendingOrdersHandler;
use App\Application\Message\ExpirePendingOrders;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\UserId;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ExpirePendingOrdersHandlerTest extends KernelTestCase
{
    public function testExpirePendingOrdersCancelsOldOrdersAndReleasesSeats(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $clock = $container->get(ClockInterface::class);

        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $clock,
            $container->get(IdGeneratorInterface::class),
            uniqid()
        );
        $userId = $data['user']->rawId();

        $container->get(ReserveSeatsHandler::class)(new ReserveSeatsCommand($userId, [$data['eventSeat']->rawId()]));
        $em->clear();

        $orders = $container->get(OrderRepositoryInterface::class)->findByUserId(new UserId($userId));
        self::assertSame(OrderStatus::Pending, $orders[0]->status());

        $handler = clone $container->get(ExpirePendingOrdersHandler::class);
        $reflection = new \ReflectionProperty(ExpirePendingOrdersHandler::class, 'pendingOrderTtlMinutes');
        $reflection->setValue($handler, 0);

        $handler(new ExpirePendingOrders());
        $em->clear();

        $updatedOrders = $container->get(OrderRepositoryInterface::class)->findByUserId(new UserId($userId));
        self::assertSame(OrderStatus::Cancelled, $updatedOrders[0]->status());
    }
}
