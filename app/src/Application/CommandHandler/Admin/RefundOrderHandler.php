<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\OrderId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private SeatAvailabilityCacheInterface $seatAvailabilityCache,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RefundOrderCommand $command): void
    {
        $orderIdVo = new OrderId($command->orderId);
        $affectedEventIds = [];

        $this->transactionManager->transactional(function () use ($orderIdVo, &$affectedEventIds): array {
            $order = $this->orders->findById($orderIdVo);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            $order->refund($this->clock);

            foreach ($order->tickets() as $ticket) {
                $ticket->refund();
            }

            foreach ($this->eventSeats->lockAndFindByIds($this->eventSeatIds($order)) as $eventSeat) {
                $eventSeat->unsell();
                $affectedEventIds[$eventSeat->event()->id()->toString()] = true;
            }

            $this->orders->save($order);

            return $order->releaseEvents();
        });

        foreach (array_keys($affectedEventIds) as $eventId) {
            $this->seatAvailabilityCache->invalidate($eventId);
        }
    }

    /** @return EventSeatId[] */
    private function eventSeatIds(Order $order): array
    {
        return array_map(
            static fn ($ticket) => $ticket->eventSeatId(),
            $order->tickets(),
        );
    }
}
