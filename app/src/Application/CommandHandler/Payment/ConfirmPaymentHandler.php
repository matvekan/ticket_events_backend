<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
final readonly class ConfirmPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ConfirmPaymentCommand $command): void
    {
        $orderId = $command->orderId();
        $userId = $command->userId();
        $orderIdVo = new OrderId($orderId->toRfc4122());
        $userIdVo = $userId !== null ? new UserId($userId->toRfc4122()) : null;

        $this->transactionManager->transactional(function () use ($orderIdVo, $userIdVo): array {
            $order = $this->orders->findById($orderIdVo);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            if ($userIdVo !== null && !$order->userId()->equals($userIdVo)) {
                throw new AccessDeniedException('You do not own this order.');
            }

            foreach ($order->tickets() as $ticket) {
                $ticket->eventSeat()->sell();
            }

            $order->pay($this->clock);
            $this->orders->save($order);

            $payment = $this->payments->findByOrderId($orderIdVo);
            if ($payment !== null) {
                $payment->markPaid($this->clock);
                $this->payments->save($payment);
            }

            return $order->releaseEvents();
        });
    }
}