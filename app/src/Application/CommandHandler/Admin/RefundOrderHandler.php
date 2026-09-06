<?php declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Shared\CacheInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\Domain\Shared\ClockInterface;

#[AsMessageHandler]
final readonly class RefundOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private EventSeatRepositoryInterface $eventSeats,
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
        private CacheInterface $seatAvailabilityCache,
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

            foreach ($this->eventSeats->lockAndFindByIds($this->eventSeatIds($order)) as $eventSeat) {
                $eventSeat->unsell();
                $affectedEventIds[$eventSeat->event()->id()->toString()] = true;
            }

            $this->refundPayments($order);
            $this->orders->save($order);

            return $order->releaseEvents();
        });

        foreach (array_keys($affectedEventIds) as $eventId) {
            $this->seatAvailabilityCache->delete($eventId);
        }
    }

    private function eventSeatIds(Order $order): array
    {
        return array_map(
            static fn ($ticket) => $ticket->eventSeatId(),
            $order->tickets(),
        );
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
