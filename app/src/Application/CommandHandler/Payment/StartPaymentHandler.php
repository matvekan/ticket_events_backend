<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Exception\AccessDeniedException;
use App\Application\Exception\PersistenceConstraintViolationException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Payment;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class StartPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function __invoke(StartPaymentCommand $command): void
    {
        $orderIdVo = new OrderId($command->orderId);
        $userIdVo = $command->userId !== null ? new UserId($command->userId) : null;

        try {
            $this->startOnce($orderIdVo, $userIdVo);
        } catch (PersistenceConstraintViolationException) {
            // A concurrent request created the payment row first; it wins.
            throw new BusinessRuleViolationException('A payment for this order is already being processed.');
        }
    }

    private function startOnce(OrderId $orderIdVo, ?UserId $userIdVo): void
    {
        $this->transactionManager->transactional(function () use ($orderIdVo, $userIdVo): void {
            $order = $this->orders->findById($orderIdVo);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            if ($userIdVo !== null && !$order->userId()->equals($userIdVo)) {
                throw new AccessDeniedException('You do not own this order.');
            }

            if ($order->status() !== OrderStatus::Pending) {
                throw new BusinessRuleViolationException('Only pending orders can be paid.');
            }

            $existing = $this->payments->findByOrderId($orderIdVo);
            if ($existing !== null) {
                if ($existing->status() === PaymentStatus::Pending) {
                    return;
                }

                $existing->restart();
                $this->payments->save($existing);

                return;
            }

            $payment = Payment::place(
                $order->id(),
                $order->totalPrice()->amount(),
                $this->clock,
                $this->ids,
            );
            $this->payments->save($payment);
        });
    }
}
