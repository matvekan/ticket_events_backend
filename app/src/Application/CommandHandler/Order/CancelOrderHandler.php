<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Exception\AccessDeniedException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\CacheInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private CacheInterface $seatAvailabilityCache,
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
                $affected = $this->releaseTickets($order, refund: true);
            } else {
                $order->cancel($this->clock);
                $affected = $this->releaseTickets($order, refund: false);
            }

            foreach ($affected as $eventId) {
                $affectedEventIds[$eventId] = true;
            }

            $this->orders->save($order);

            return $order->releaseEvents();
        });

        foreach (array_keys($affectedEventIds) as $eventId) {
            $this->seatAvailabilityCache->delete($eventId);
        }
    }

    private function findOwnedOrder(OrderId $orderId, ?UserId $userId): Order
    {
        $order = $this->orders->findById($orderId);
        if (! $order) {
            throw new EntityNotFoundException('Order not found.');
        }

        if ($userId !== null && ! $order->userId()->equals($userId)) {
            throw new AccessDeniedException('You do not own this order.');
        }

        return $order;
    }

    private function releaseTickets(Order $order, bool $refund): array
    {
        $eventSeatIds = array_map(
            static fn ($ticket) => $ticket->eventSeatId(),
            $order->tickets(),
        );

        $eventSeats = $this->eventSeats->lockAndFindByIds($eventSeatIds);

        $affectedEvents = [];
        foreach ($eventSeats as $eventSeat) {
            $refund ? $eventSeat->unsell() : $eventSeat->release();
            $affectedEvents[] = $eventSeat->event()->id()->toString();
        }

        return $affectedEvents;
    }
}
