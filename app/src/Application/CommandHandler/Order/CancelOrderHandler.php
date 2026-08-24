<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
final readonly class CancelOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $orderId = new OrderId($command->orderId()->toRfc4122());
        $userId = $command->userId() !== null ? new UserId($command->userId()->toRfc4122()) : null;

        $this->transactionManager->transactional(function () use ($orderId, $userId): array {
            $order = $this->findOwnedOrder($orderId, $userId);
            $wasPaid = $order->status() === OrderStatus::Paid;

            if ($wasPaid) {
                $order->refund($this->clock);
                $this->releaseTickets($order, refund: true);
            } else {
                $order->cancel($this->clock);
                $this->releaseTickets($order, refund: false);
            }

            $this->orders->save($order);

            return $order->releaseEvents();
        });
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

    private function releaseTickets(Order $order, bool $refund): void
    {
        foreach ($order->tickets() as $ticket) {
            $refund ? $ticket->refund() : $ticket->cancel();
            $refund ? $ticket->eventSeat()->unsell() : $ticket->eventSeat()->release();
        }
    }
}
