<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\ConfirmPaymentCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class ConfirmPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly EventBusInterface $eventBus,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ConfirmPaymentCommand $command): void
    {
        $order = null;

        $this->transactionManager->transactional(function () use ($command, &$order): void {
            $order = $this->orderRepo->findById($command->orderId);
            if (!$order) {
                throw new \DomainException('Order not found.');
            }

            foreach ($order->getTickets() as $ticket) {
                $ticket->getEventSeat()->sell();
            }

            $order->pay();
            $this->orderRepo->save($order);
        });

        if ($order !== null) {
            $ticketIds = array_map(fn ($t): Uuid => $t->getId(), $order->getTickets()->toArray());
            $this->eventBus->dispatch(new OrderPaidEvent(
                $order->getId(),
                $order->getUser()->getId(),
                $order->getTotalAmount(),
                $ticketIds,
            ));
        }
    }
}
