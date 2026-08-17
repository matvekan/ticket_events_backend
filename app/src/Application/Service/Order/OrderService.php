<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\OrderStatus;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final readonly class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function cancel(Uuid $orderId, ?Uuid $userId = null): void
    {
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

            foreach ($ticketList = $order->tickets()->toArray() as $ticket) {
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

    public function refund(Uuid $orderId): void
    {
        $events = $this->transactionManager->transactional(function () use ($orderId): array {
            $order = $this->orders->findById($orderId);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            $order->refund();

            foreach ($ticketList = $order->tickets()->toArray() as $ticket) {
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
