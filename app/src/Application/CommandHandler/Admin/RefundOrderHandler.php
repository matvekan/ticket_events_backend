<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class RefundOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(RefundOrderCommand $command): void
    {
        $events = $this->transactionManager->transactional(function () use ($command): array {
            $order = $this->orders->findById(Uuid::fromString($command->orderId));
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            $order->refund();

            $ticketList = $order->tickets()->toArray();

            foreach ($ticketList as $ticket) {
                $ticket->eventSeat()->unsell();
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