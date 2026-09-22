<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\FailPaymentCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\CommandHandler\Payment\ConfirmPaymentHandler;
use App\Application\CommandHandler\Payment\FailPaymentHandler;
use App\Application\CommandHandler\Payment\StartPaymentHandler;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\UserId;
use App\Tests\Integration\Support\IntegrationFixture;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PaymentHandlersTest extends KernelTestCase
{
    public function testStartPaymentCreatesPendingPaymentForOrder(): void
    {
        $context = $this->createReservedOrderContext();

        $this->getStartPaymentHandler()->__invoke(new StartPaymentCommand($context['orderId'], $context['userId']));

        $this->assertPaymentStatusIs($context['orderId'], PaymentStatus::Pending);
    }

    public function testStartPaymentIsIdempotentWhenPaymentIsAlreadyPending(): void
    {
        $context = $this->createReservedOrderContext();
        $handler = $this->getStartPaymentHandler();
        $handler(new StartPaymentCommand($context['orderId'], $context['userId']));

        $handler(new StartPaymentCommand($context['orderId'], $context['userId']));

        $this->assertPaymentStatusIs($context['orderId'], PaymentStatus::Pending);
    }

    public function testStartPaymentRestartsPaymentWhenPreviousAttemptFailed(): void
    {
        $context = $this->createReservedOrderContext();
        $this->getStartPaymentHandler()->__invoke(new StartPaymentCommand($context['orderId'], $context['userId']));
        $this->getFailPaymentHandler()->__invoke(new FailPaymentCommand($context['orderId'], $context['userId']));

        $this->getStartPaymentHandler()->__invoke(new StartPaymentCommand($context['orderId'], $context['userId']));

        $this->assertPaymentStatusIs($context['orderId'], PaymentStatus::Pending);
    }

    public function testStartPaymentThrowsBusinessRuleViolationWhenOrderIsAlreadyPaid(): void
    {
        $context = $this->createPaidOrderContext();

        $this->expectException(BusinessRuleViolationException::class);

        $this->getStartPaymentHandler()->__invoke(new StartPaymentCommand($context['orderId'], $context['userId']));
    }

    public function testFailPaymentTransitionsPendingPaymentToFailed(): void
    {
        $context = $this->createPendingPaymentContext();

        $this->getFailPaymentHandler()->__invoke(new FailPaymentCommand($context['orderId'], $context['userId']));

        $this->assertPaymentStatusIs($context['orderId'], PaymentStatus::Failed);
    }

    public function testFailPaymentThrowsBusinessRuleViolationWhenPaymentIsAlreadyPaid(): void
    {
        $context = $this->createPaidOrderContext();

        $this->expectException(BusinessRuleViolationException::class);

        $this->getFailPaymentHandler()->__invoke(new FailPaymentCommand($context['orderId'], $context['userId']));
    }

    public function testFailPaymentThrowsEntityNotFoundWhenPaymentDoesNotExist(): void
    {
        self::bootKernel();
        $handler = static::getContainer()->get(FailPaymentHandler::class);

        $this->expectException(EntityNotFoundException::class);

        $handler(new FailPaymentCommand('00000000-0000-4000-8000-000000000000', FixedIdGenerator::uuid(1)));
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

        return ['userId' => $data['user']->rawId(), 'orderId' => $order->rawId()];
    }

    private function createPendingPaymentContext(): array
    {
        $context = $this->createReservedOrderContext();
        $this->getStartPaymentHandler()->__invoke(new StartPaymentCommand($context['orderId'], $context['userId']));
        $this->clearEntityManager();

        return $context;
    }

    private function createPaidOrderContext(): array
    {
        $context = $this->createPendingPaymentContext();
        static::getContainer()->get(ConfirmPaymentHandler::class)->__invoke(new \App\Application\Command\Payment\ConfirmPaymentCommand($context['orderId'], $context['userId']));
        $this->clearEntityManager();

        return $context;
    }

    private function findLatestOrderForUser(string $userId): \App\Domain\Entity\Order
    {
        return static::getContainer()->get(OrderRepositoryInterface::class)->findByUserId(new UserId($userId))[0];
    }

    private function getStartPaymentHandler(): StartPaymentHandler
    {
        return static::getContainer()->get(StartPaymentHandler::class);
    }

    private function getFailPaymentHandler(): FailPaymentHandler
    {
        return static::getContainer()->get(FailPaymentHandler::class);
    }

    private function assertPaymentStatusIs(string $orderId, PaymentStatus $expected): void
    {
        $this->clearEntityManager();
        $payment = static::getContainer()->get(PaymentRepositoryInterface::class)->findByOrderId(new OrderId($orderId));

        self::assertNotNull($payment);
        self::assertSame($expected, $payment->status());
    }

    private function clearEntityManager(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)->clear();
    }
}
