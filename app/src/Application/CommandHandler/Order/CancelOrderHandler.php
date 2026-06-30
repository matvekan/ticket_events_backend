<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Event\OrderCancelledEvent;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class CancelOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly EventBusInterface $eventBus,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = null;

        $this->transactionManager->transactional(function () use ($command, &$order): void {
            $order = $this->orderRepo->findById($command->orderId);
            if (!$order) {
                throw new \DomainException('Order not found.');
            }

            foreach ($order->getTickets() as $ticket) {
                $ticket->getEventSeat()->release();
            }

            $order->cancel();
            $this->orderRepo->save($order);
        });

        if ($order !== null) {
            $eventSeatIds = $order->getTickets()
                ->map(fn ($t): Uuid => $t->getEventSeat()->getId())
                ->toArray();
            $this->eventBus->dispatch(new OrderCancelledEvent($order->getId(), $order->getUser()->getId(), $eventSeatIds));
        }
    }
}
