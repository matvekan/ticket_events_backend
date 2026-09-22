<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Event\CreateEventCommand;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\CommandHandler\Event\CreateEventHandler;
use App\Application\CommandHandler\Event\PublishEventHandler;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\CommandHandler\Payment\ConfirmPaymentHandler;
use App\Application\CommandHandler\Payment\StartPaymentHandler;
use App\Application\CommandHandler\Venue\CreateVenueHandler;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrinePaymentRepository with real PostgreSQL.
 */
final class PaymentRepositoryTest extends KernelTestCase
{
    public function testFindByOrderIdReturnsPaymentAfterStart(): void
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

        $orders = $container->get(\App\Domain\Repository\OrderRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($data['user']->rawId())
        );
        $order = $orders[0];

        $container->get(StartPaymentHandler::class)(new StartPaymentCommand($order->rawId(), $data['user']->rawId()));
        $em->clear();

        $payment = $container->get(PaymentRepositoryInterface::class)->findByOrderId($order->id());

        self::assertNotNull($payment);
        self::assertSame($order->rawId(), $payment->orderId()->toString());
        self::assertSame(PaymentStatus::Pending, $payment->status());
        self::assertSame($order->totalPrice()->amount(), $payment->amount());
    }

    public function testFindByOrderIdReturnsNullForOrderWithoutPayment(): void
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

        $orders = $container->get(\App\Domain\Repository\OrderRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($data['user']->rawId())
        );
        $order = $orders[0];

        $payment = $container->get(PaymentRepositoryInterface::class)->findByOrderId($order->id());

        self::assertNull($payment);
    }

    public function testSavePersistsPaymentStatusTransitions(): void
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

        $orders = $container->get(\App\Domain\Repository\OrderRepositoryInterface::class)->findByUserId(
            new \App\Domain\ValueObject\UserId($data['user']->rawId())
        );
        $order = $orders[0];

        $container->get(StartPaymentHandler::class)(new StartPaymentCommand($order->rawId(), $data['user']->rawId()));
        $container->get(ConfirmPaymentHandler::class)(new ConfirmPaymentCommand($order->rawId(), $data['user']->rawId()));
        $em->clear();

        $payment = $container->get(PaymentRepositoryInterface::class)->findByOrderId($order->id());

        self::assertNotNull($payment);
        self::assertSame(PaymentStatus::Paid, $payment->status());
    }
}