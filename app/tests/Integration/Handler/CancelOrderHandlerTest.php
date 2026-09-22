<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\CommandHandler\Order\CancelOrderHandler;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\CommandHandler\Payment\ConfirmPaymentHandler;
use App\Application\CommandHandler\Payment\StartPaymentHandler;
use App\Application\Exception\AccessDeniedException;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\OrderCancelledEvent;
use App\Domain\Event\OrderRefundedEvent;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\SeatStatus;
use App\Domain\ValueObject\UserId;
use App\Tests\Integration\Support\IntegrationFixture;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CancelOrderHandlerTest extends KernelTestCase
{
    public function testCancelPendingOrderTransitionsToCancelledReleasesSeatAndWritesOutbox(): void
    {
        $context = $this->createReservedOrderContext();
        $handler = $this->getCancelOrderHandler();

        $handler(new CancelOrderCommand($context['orderId'], $context['userId']));

        $this->assertOrderStatusIs($context['orderId'], OrderStatus::Cancelled);
        $this->assertEventSeatStatusIs($context['eventSeatId'], SeatStatus::Free);
        $this->assertOutboxContainsEvent($context['orderId'], OrderCancelledEvent::class);
    }

    public function testCancelPaidOrderTransitionsToRefundedUnsellsSeatAndWritesOutbox(): void
    {
        $context = $this->createPaidOrderContext();
        $handler = $this->getCancelOrderHandler();

        $handler(new CancelOrderCommand($context['orderId'], $context['userId']));

        $this->assertOrderStatusIs($context['orderId'], OrderStatus::Refunded);
        $this->assertEventSeatStatusIs($context['eventSeatId'], SeatStatus::Free);
        $this->assertOutboxContainsEvent($context['orderId'], OrderRefundedEvent::class);
    }

    public function testCancelOrderThrowsEntityNotFoundWhenOrderDoesNotExist(): void
    {
        self::bootKernel();
        $handler = static::getContainer()->get(CancelOrderHandler::class);

        $this->expectException(EntityNotFoundException::class);

        $handler(new CancelOrderCommand('00000000-0000-4000-8000-000000000000', FixedIdGenerator::uuid(1)));
    }

    public function testCancelOrderThrowsAccessDeniedWhenUserDoesNotOwnOrder(): void
    {
        $context = $this->createReservedOrderContext();
        $handler = $this->getCancelOrderHandler();

        $this->expectException(AccessDeniedException::class);

        $handler(new CancelOrderCommand($context['orderId'], FixedIdGenerator::uuid(2)));
    }

    private function createReservedOrderContext(): array
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

        $container->get(ReserveSeatsHandler::class)->__invoke(new ReserveSeatsCommand($data['user']->rawId(), [$data['eventSeat']->rawId()]));
        $em->clear();

        $order = $this->findLatestOrderForUser($data['user']->rawId());

        return [
            'orderId' => $order->rawId(),
            'userId' => $data['user']->rawId(),
            'eventSeatId' => $data['eventSeat']->rawId(),
        ];
    }

    private function createPaidOrderContext(): array
    {
        $context = $this->createReservedOrderContext();
        $container = static::getContainer();

        $container->get(StartPaymentHandler::class)->__invoke(new StartPaymentCommand($context['orderId'], $context['userId']));
        $container->get(ConfirmPaymentHandler::class)->__invoke(new ConfirmPaymentCommand($context['orderId'], $context['userId']));
        $container->get(EntityManagerInterface::class)->clear();

        return $context;
    }

    private function findLatestOrderForUser(string $userId): \App\Domain\Entity\Order
    {
        return static::getContainer()->get(OrderRepositoryInterface::class)->findByUserId(new UserId($userId))[0];
    }

    private function getCancelOrderHandler(): CancelOrderHandler
    {
        return static::getContainer()->get(CancelOrderHandler::class);
    }

    private function assertOrderStatusIs(string $orderId, OrderStatus $expected): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $order = static::getContainer()->get(OrderRepositoryInterface::class)->findById(new OrderId($orderId));

        self::assertSame($expected, $order->status());
    }

    private function assertEventSeatStatusIs(string $eventSeatId, SeatStatus $expected): void
    {
        $seat = static::getContainer()->get(EntityManagerInterface::class)->getRepository(EventSeat::class)->find($eventSeatId);

        self::assertSame($expected, $seat->status());
    }

    private function assertOutboxContainsEvent(string $orderId, string $eventClass): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $messages = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => $eventClass]);

        self::assertTrue($this->containsOutboxEventForOrder($messages, $orderId, $eventClass));
    }

    private function containsOutboxEventForOrder(array $messages, string $orderId, string $eventClass): bool
    {
        foreach ($messages as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof OrderCancelledEvent && $event->orderId() === $orderId) {
                return true;
            }

            if ($event instanceof OrderRefundedEvent && $event->orderId() === $orderId) {
                return true;
            }
        }

        return false;
    }
}
