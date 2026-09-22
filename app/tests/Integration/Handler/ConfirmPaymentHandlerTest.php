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
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\UserId;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ConfirmPaymentHandlerTest extends KernelTestCase
{
    public function testConfirmPaymentMarksOrderAndPaymentAsPaidAndWritesOutboxEvent(): void
    {
        $context = $this->createReservedOrderContext();
        $beforeIds = $this->collectExistingOrderPaidOutboxIds();

        $this->getConfirmPaymentHandler()->__invoke(new ConfirmPaymentCommand($context['orderId'], $context['userId']));

        $this->assertOrderIsPaid($context['orderId']);
        $this->assertPaymentIsPaid($context['orderId']);
        $this->assertOrderPaidEventWasWrittenForOrder($context['orderId'], $beforeIds);
    }

    public function testConfirmPaymentIsIdempotentWhenOrderIsAlreadyPaid(): void
    {
        $context = $this->createReservedOrderContext();
        $handler = $this->getConfirmPaymentHandler();
        $handler(new ConfirmPaymentCommand($context['orderId'], $context['userId']));

        $handler(new ConfirmPaymentCommand($context['orderId'], $context['userId']));

        $this->assertOrderIsPaid($context['orderId']);
    }

    public function testConfirmPaymentThrowsEntityNotFoundWhenPaymentDoesNotExist(): void
    {
        $context = $this->createReservedOrderWithoutPaymentContext();

        $this->expectException(\App\Domain\Exception\EntityNotFoundException::class);

        $this->getConfirmPaymentHandler()->__invoke(new ConfirmPaymentCommand($context['orderId'], $context['userId']));
    }

    private function createReservedOrderContext(): array
    {
        $eventContext = $this->createPublishedEventContextWithoutPayment();
        $container = static::getContainer();

        $container->get(ReserveSeatsHandler::class)->__invoke(new ReserveSeatsCommand($eventContext['userId'], [$eventContext['eventSeatId']]));
        $this->clearEntityManager();

        $order = $this->findLatestOrderForUser($eventContext['userId']);
        $container->get(StartPaymentHandler::class)->__invoke(new StartPaymentCommand($order->rawId(), $eventContext['userId']));

        return [
            'userId' => $eventContext['userId'],
            'orderId' => $order->rawId(),
        ];
    }

    private function createPublishedEventContextWithoutPayment(): array
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(ClockInterface::class),
            $container->get(IdGeneratorInterface::class),
            uniqid()
        );

        return [
            'userId' => $data['user']->rawId(),
            'eventSeatId' => $data['eventSeat']->rawId(),
            'orderId' => null,
        ];
    }

    private function createReservedOrderWithoutPaymentContext(): array
    {
        $eventContext = $this->createPublishedEventContextWithoutPayment();
        $container = static::getContainer();

        $container->get(ReserveSeatsHandler::class)->__invoke(new ReserveSeatsCommand($eventContext['userId'], [$eventContext['eventSeatId']]));
        $this->clearEntityManager();

        $order = $this->findLatestOrderForUser($eventContext['userId']);

        return ['userId' => $eventContext['userId'], 'orderId' => $order->rawId()];
    }

    private function findLatestOrderForUser(string $userId): \App\Domain\Entity\Order
    {
        $orders = static::getContainer()->get(OrderRepositoryInterface::class)->findByUserId(new UserId($userId));

        return $orders[0];
    }

    private function getConfirmPaymentHandler(): ConfirmPaymentHandler
    {
        return static::getContainer()->get(ConfirmPaymentHandler::class);
    }

    private function collectExistingOrderPaidOutboxIds(): array
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return array_map(
            static fn (OutboxMessage $m): string => $m->id(),
            $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => OrderPaidEvent::class])
        );
    }

    private function assertOrderIsPaid(string $orderId): void
    {
        $this->clearEntityManager();
        $order = static::getContainer()->get(OrderRepositoryInterface::class)->findById(new OrderId($orderId));

        self::assertSame(OrderStatus::Paid, $order->status());
    }

    private function assertPaymentIsPaid(string $orderId): void
    {
        $payment = static::getContainer()->get(PaymentRepositoryInterface::class)->findByOrderId(new OrderId($orderId));

        self::assertSame(PaymentStatus::Paid, $payment->status());
    }

    private function assertOrderPaidEventWasWrittenForOrder(string $orderId, array $beforeIds): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $outbox = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => OrderPaidEvent::class]);

        $found = $this->containsOrderPaidEventForOrder($outbox, $orderId, $beforeIds);

        self::assertTrue($found);
    }

    private function containsOrderPaidEventForOrder(array $outbox, string $orderId, array $beforeIds): bool
    {
        foreach ($outbox as $message) {
            if (\in_array($message->id(), $beforeIds, true)) {
                continue;
            }

            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof OrderPaidEvent && $event->orderId() === $orderId) {
                return true;
            }
        }

        return false;
    }

    private function clearEntityManager(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)->clear();
    }
}
