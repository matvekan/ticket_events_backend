<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\FailPaymentCommand;
use App\Application\Exception\AccessDeniedException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FailPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(FailPaymentCommand $command): void
    {
        $orderIdVo = new OrderId($command->orderId);
        $userIdVo = $command->userId !== null ? new UserId($command->userId) : null;

        $this->transactionManager->transactional(function () use ($orderIdVo, $userIdVo): void {
            $order = $this->orders->findById($orderIdVo);
            if (! $order) {
                throw new EntityNotFoundException('Order not found.');
            }
            if ($userIdVo !== null && ! $order->userId()->equals($userIdVo)) {
                throw new AccessDeniedException('You do not own this order.');
            }

            $payment = $this->payments->findByOrderId($orderIdVo);
            if ($payment === null) {
                throw new EntityNotFoundException('Payment not found for this order.');
            }

            if ($payment->status() !== PaymentStatus::Pending) {
                throw new BusinessRuleViolationException('Only pending payments can be declined.');
            }

            $payment->markFailed($this->clock);
            $this->payments->save($payment);
        });
    }
}
