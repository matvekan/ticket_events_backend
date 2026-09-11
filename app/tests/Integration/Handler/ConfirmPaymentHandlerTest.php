<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\CommandHandler\Payment\ConfirmPaymentHandler;
use App\Application\CommandHandler\Payment\StartPaymentHandler;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Application layer: ConfirmPaymentHandler writes OrderPaidEvent to messenger_outbox
 * inside the same DB transaction (TransactionManagerInterface).
 */
final class ConfirmPaymentHandlerTest extends KernelTestCase
{
    public function testConfirmPaymentMarksOrderPaidAndWritesOutbox(): void
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
        $userId = $data['user']->rawId();
        $seatId = $data['eventSeat']->rawId();

        $container->get(ReserveSeatsHandler::class)(new ReserveSeatsCommand($userId, [$seatId]));
        $em->clear();
        $order = $container->get(OrderRepositoryInterface::class)->findByUserId(new \App\Domain\ValueObject\UserId($userId))[0];
        $orderId = $order->rawId();
        $container->get(StartPaymentHandler::class)(new StartPaymentCommand($orderId, $userId));
        $beforeIds = array_map(
            static fn (OutboxMessage $m): string => $m->id(),
            $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => OrderPaidEvent::class])
        );

        // Act
        $container->get(ConfirmPaymentHandler::class)(new ConfirmPaymentCommand($orderId, $userId));

        // Assert: order paid
        $em->clear();
        $paid = $container->get(OrderRepositoryInterface::class)->findById(new OrderId($orderId));
        self::assertSame(OrderStatus::Paid, $paid->status());

        // Assert: payment paid
        $payment = $container->get(PaymentRepositoryInterface::class)->findByOrderId(new OrderId($orderId));
        self::assertSame(PaymentStatus::Paid, $payment->status());

        // Assert: outbox contains OrderPaidEvent for this order (only rows created by this test)
        $outbox = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => OrderPaidEvent::class]);
        $found = false;
        foreach ($outbox as $message) {
            if (\in_array($message->id(), $beforeIds, true)) {
                continue;
            }
            /** @var OrderPaidEvent $event */
            $event = unserialize(base64_decode($message->body()));
            if ($event->orderId() === $orderId) {
                $found = true;

                break;
            }
        }
        self::assertTrue($found, 'OrderPaidEvent should be written to messenger_outbox');
    }

    public function testConfirmPaymentIsIdempotentWhenAlreadyPaid(): void
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
        $userId = $data['user']->rawId();
        $container->get(ReserveSeatsHandler::class)(new ReserveSeatsCommand($userId, [$data['eventSeat']->rawId()]));
        $em->clear();
        $order = $container->get(OrderRepositoryInterface::class)->findByUserId(new \App\Domain\ValueObject\UserId($userId))[0];
        $container->get(StartPaymentHandler::class)(new StartPaymentCommand($order->rawId(), $userId));
        $handler = $container->get(ConfirmPaymentHandler::class);
        $handler(new ConfirmPaymentCommand($order->rawId(), $userId));

        // Act (second confirm must not fail)
        $handler(new ConfirmPaymentCommand($order->rawId(), $userId));

        // Assert
        $em->clear();
        $paid = $container->get(OrderRepositoryInterface::class)->findById(new OrderId($order->rawId()));
        self::assertSame(OrderStatus::Paid, $paid->status());
    }
}
