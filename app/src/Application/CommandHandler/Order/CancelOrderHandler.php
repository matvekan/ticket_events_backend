<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\OrderStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class CancelOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
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

            if ($order->status() === OrderStatus::Paid) {
                $order->refund();
            } else {
                $order->cancel();
            }

            $ticketList = $order->tickets()->toArray();

            foreach ($ticketList as $ticket) {
                $eventSeat = $ticket->eventSeat();
                try {
                    $eventSeat->release();
                } catch (\DomainException) {
                    $eventSeat->unsell();
                }
            }

            foreach ($ticketList as $ticket) {
                $order->removeTicket($ticket);
            }

            $this->orders->save($order);

            return $order->releaseEvents();
        });

        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}