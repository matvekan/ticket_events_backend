<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Payment;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
final readonly class StartPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(StartPaymentCommand $command): void
    {
        $orderId = $command->orderId();
        $userId = $command->userId();
        $orderIdVo = new OrderId($orderId->toRfc4122());
        $userIdVo = $userId !== null ? new UserId($userId->toRfc4122()) : null;

        $this->transactionManager->transactional(function () use ($orderId, $orderIdVo, $userIdVo): void {
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

            $payment = Payment::create($order, $order->totalPrice()->amount());
            $this->payments->save($payment);
        });
    }
}