<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Payment;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\PaymentStatus;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final readonly class PaymentService
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function startPayment(Uuid $orderId, ?Uuid $userId = null): Payment
    {
        return $this->transactionManager->transactional(function () use ($orderId, $userId): Payment {
            $order = $this->orders->findById($orderId);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            if ($userId !== null && !$order->user()->id()->equals($userId)) {
                throw new AccessDeniedException('You do not own this order.');
            }

            if ($order->status() !== OrderStatus::Pending) {
                throw new BusinessRuleViolationException('Only pending orders can be paid.');
            }

            $existing = $this->payments->findByOrderId($orderId);
            if ($existing !== null) {
                if ($existing->status() === PaymentStatus::Pending) {
                    return $existing;
                }

                $existing->restart();
                $this->payments->save($existing);

                return $existing;
            }

            $payment = Payment::create($order, $order->totalPrice()->amount());
            $this->payments->save($payment);

            return $payment;
        });
    }

    public function failPayment(Uuid $paymentId): void
    {
        $this->transactionManager->transactional(function () use ($paymentId): void {
            $payment = $this->payments->findById($paymentId);
            if (!$payment) {
                throw new EntityNotFoundException('Payment not found.');
            }

            if ($payment->status() !== PaymentStatus::Pending) {
                throw new BusinessRuleViolationException('Only pending payments can be declined.');
            }

            $payment->markFailed();
            $this->payments->save($payment);
        });
    }

    public function confirmPayment(Uuid $orderId, ?Uuid $userId = null): void
    {
        $events = $this->transactionManager->transactional(function () use ($orderId, $userId): array {
            $order = $this->orders->findById($orderId);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            if ($userId !== null && !$order->user()->id()->equals($userId)) {
                throw new AccessDeniedException('You do not own this order.');
            }

            foreach ($order->tickets() as $ticket) {
                $ticket->eventSeat()->sell();
            }

            $order->pay();
            $this->orders->save($order);

            $payment = $this->payments->findByOrderId($orderId);
            if ($payment !== null) {
                $payment->markPaid();
                $this->payments->save($payment);
            }

            return $order->releaseEvents();
        });

        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
