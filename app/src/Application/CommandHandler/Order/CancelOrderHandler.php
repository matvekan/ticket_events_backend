<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Exception\AccessDeniedException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private SeatAvailabilityCacheInterface $seatAvailabilityCache,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $orderId = new OrderId($command->orderId);
        $userId = $command->userId !== null ? new UserId($command->userId) : null;

        $affectedEventIds = [];

        $this->transactionManager->transactional(function () use ($orderId, $userId, &$affectedEventIds): array {
            $order = $this->findOwnedOrder($orderId, $userId);
            $wasPaid = $order->status() === OrderStatus::Paid;

            if ($wasPaid) {
                $order->refund($this->clock);
                $this->releaseTickets($order, refund: true);
            } else {
                $order->cancel($this->clock);
                $this->releaseTickets($order, refund: false);
            }

            foreach ($order->tickets() as $ticket) {
                $affectedEventIds[$this->resolveEventId($ticket->eventSeatId())] = true;
            }

            $this->orders->save($order);

            return $order->releaseEvents();
        });

        // Fresh seat map right after commit (TTL remains as a safety net).
        foreach (array_keys($affectedEventIds) as $eventId) {
            $this->seatAvailabilityCache->invalidate($eventId);
        }
    }

    private function findOwnedOrder(OrderId $orderId, ?UserId $userId): Order
    {
        $order = $this->orders->findById($orderId);
        if (!$order) {
            throw new EntityNotFoundException('Order not found.');
        }

        // System callers (expiry scheduler) have no user context;
        // interactive calls must prove ownership.
        if ($userId !== null && !$order->userId()->equals($userId)) {
            throw new AccessDeniedException('You do not own this order.');
        }

        return $order;
    }

    private function releaseTickets(Order $order, bool $refund): void
    {
        $eventSeatIds = array_map(
            static fn ($ticket) => $ticket->eventSeatId(),
            $order->tickets(),
        );
        $eventSeats = $this->eventSeats->lockAndFindByIds($eventSeatIds);

        foreach ($order->tickets() as $ticket) {
            $refund ? $ticket->refund() : $ticket->cancel();
        }

        foreach ($eventSeats as $eventSeat) {
            $refund ? $eventSeat->unsell() : $eventSeat->release();
        }
    }

    private function resolveEventId(EventSeatId $eventSeatId): string
    {
        $eventSeat = $this->eventSeats->findById($eventSeatId);

        return $eventSeat !== null ? $eventSeat->event()->id()->toString() : '';
    }
}
