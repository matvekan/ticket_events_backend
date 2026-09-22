<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\RefundTicketsCommand;
use App\Application\Exception\AccessDeniedException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\CacheInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundTicketsHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private CacheInterface $seatAvailabilityCache,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RefundTicketsCommand $command): void
    {
        $orderId = new OrderId($command->orderId);
        $userId = $command->userId !== null ? new UserId($command->userId) : null;
        $affectedEventIds = [];

        $this->transactionManager->transactional(function () use ($command, $orderId, $userId, &$affectedEventIds): void {
            $order = $this->findOwnedOrder($orderId, $userId);

            $eventSeatIdsToRelease = [];
            foreach ($order->tickets() as $ticket) {
                if (\in_array($ticket->id()->toString(), $command->ticketIds, true)) {
                    $eventSeatIdsToRelease[] = $ticket->eventSeatId();
                }
            }

            if ($eventSeatIdsToRelease === []) {
                throw new BusinessRuleViolationException('Specified tickets not found in this order.');
            }

            $eventSeats = $this->eventSeats->lockAndFindByIds($eventSeatIdsToRelease);
            $timeLimit = $this->clock->now()->modify('+24 hours');

            foreach ($eventSeats as $eventSeat) {
                if ($eventSeat->event()->date() <= $timeLimit) {
                    throw new BusinessRuleViolationException('Refund is not possible: less than 24 hours left before the event.');
                }
            }

            $order->refundSpecificTickets($command->ticketIds, $this->clock);

            foreach ($eventSeats as $eventSeat) {
                $eventSeat->unsell();
                $affectedEventIds[$eventSeat->event()->id()->toString()] = true;
            }

            $this->orders->save($order);
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
