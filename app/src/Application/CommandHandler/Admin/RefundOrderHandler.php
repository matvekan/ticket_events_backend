<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Event\OrderRefundedEvent;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class RefundOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly EventBusInterface $eventBus,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(RefundOrderCommand $command): void
    {
        $order = null;

        $this->transactionManager->transactional(function () use ($command, &$order): void {
            $order = $this->orderRepo->findById($command->orderId);
            if (!$order) {
                throw new \DomainException('Order not found.');
            }

            $order->refund();

            foreach ($order->getTickets() as $ticket) {
                $ticket->getEventSeat()->unsell();
            }

            $this->orderRepo->save($order);
        });

        if ($order !== null) {
            $eventSeatIds = $order->getTickets()
                ->map(fn ($t): Uuid => $t->getEventSeat()->getId())
                ->toArray();
            $this->eventBus->dispatch(new OrderRefundedEvent(
                $order->getId(),
                $order->getUser()->getId(),
                $order->getTotalAmount(),
                $eventSeatIds,
            ));
        }
    }
}
