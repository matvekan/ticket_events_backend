<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ConfirmPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ConfirmPaymentCommand $command): void
    {
        $orderId = Uuid::fromString($command->orderId);
        $userId = $command->userId !== null ? Uuid::fromString($command->userId) : null;

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