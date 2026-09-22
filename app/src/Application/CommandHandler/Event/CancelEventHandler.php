<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CancelEventCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\CacheInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\SeatStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventRepositoryInterface $events,
        private OrderRepositoryInterface $orders,
        private EventSeatRepositoryInterface $eventSeats,
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
        private CacheInterface $seatAvailabilityCache,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CancelEventCommand $command): void
    {
        $eventId = $command->eventId;

        $this->transactionManager->transactional(function () use ($eventId): void {
            $event = $this->events->findById(new EventId($eventId));
            if (!$event) {
                throw new EntityNotFoundException('Event not found.');
            }

            $this->cancelOrders($event);
            $this->releaseSeats($event);

            $event->cancel($this->clock);
            $this->events->save($event);
        });

        $this->seatAvailabilityCache->delete($eventId);
    }

    private function cancelOrders(Event $event): void
    {
        $ordersForEvent = $this->orders->findByEventId($event->id());

        foreach ($ordersForEvent as $order) {
            if ($order->status() === OrderStatus::Cancelled || $order->status() === OrderStatus::Refunded) {
                continue;
            }

            if ($order->status() === OrderStatus::Paid) {
                $order->refund($this->clock);
                $this->refundPayments($order);
            } else {
                $order->cancel($this->clock);
            }

            $this->orders->save($order);
        }
    }

    private function releaseSeats(Event $event): void
    {
        $eventSeatIds = array_map(
            static fn (EventSeat $eventSeat) => $eventSeat->id(),
            $this->eventSeats->findByEventId($event->id()),
        );

        if ($eventSeatIds === []) {
            return;
        }

        foreach ($this->eventSeats->lockAndFindByIds($eventSeatIds) as $eventSeat) {
            if ($eventSeat->status() === SeatStatus::Reserved) {
                $eventSeat->release();
            } elseif ($eventSeat->status() === SeatStatus::Sold) {
                $eventSeat->unsell();
            }
            $this->eventSeats->save($eventSeat);
        }
    }

    private function refundPayments(Order $order): void
    {
        $payment = $this->payments->findByOrderId($order->id());
        if ($payment !== null && $payment->status() === PaymentStatus::Paid) {
            $payment->markRefunded($this->clock);
            $this->payments->save($payment);
        }
    }
}
