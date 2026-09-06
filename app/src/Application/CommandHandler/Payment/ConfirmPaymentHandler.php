<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Exception\AccessDeniedException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\CacheInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ConfirmPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private PaymentRepositoryInterface $payments,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private CacheInterface $seatAvailabilityCache,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ConfirmPaymentCommand $command): void
    {
        $orderIdVo = new OrderId($command->orderId);
        $userIdVo = $command->userId !== null ? new UserId($command->userId) : null;

        $affectedEventIds = [];

        $this->transactionManager->transactional(function () use ($orderIdVo, $userIdVo, &$affectedEventIds): array {
            $order = $this->findOwnedOrder($orderIdVo, $userIdVo);

            $payment = $this->payments->findByOrderId($orderIdVo);
            if ($payment === null) {
                throw new EntityNotFoundException('Payment not found for this order.');
            }

            if ($order->status() === OrderStatus::Paid) {
                return [];
            }

            $eventSeatIds = array_map(
                static fn ($ticket) => $ticket->eventSeatId(),
                $order->tickets(),
            );
            $eventSeats = $this->eventSeats->lockAndFindByIds($eventSeatIds);
            foreach ($eventSeats as $eventSeat) {
                $eventSeat->sell();
                $affectedEventIds[$eventSeat->event()->id()->toString()] = true;
            }

            $order->pay($this->clock);
            foreach ($order->tickets() as $ticket) {
                $ticket->activate();
            }

            $this->orders->save($order);

            $payment->markPaid($this->clock);
            $this->payments->save($payment);

            return $order->releaseEvents();
        });

        foreach (array_keys($affectedEventIds) as $eventId) {
            $this->seatAvailabilityCache->delete($eventId);
        }
    }

    private function findOwnedOrder(OrderId $orderId, ?UserId $userId): Order
    {
        $order = $this->orders->findById($orderId);
        if (!$order) {
            throw new EntityNotFoundException('Order not found.');
        }

        if ($userId !== null && !$order->userId()->equals($userId)) {
            throw new AccessDeniedException('You do not own this order.');
        }

        return $order;
    }
}
