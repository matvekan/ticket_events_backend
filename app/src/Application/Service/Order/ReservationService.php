<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\Ticket;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\TicketCode;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Uid\Uuid;

final readonly class ReservationService
{
    private const MAX_CODE_GENERATION_ATTEMPTS = 3;

    public function __construct(
        private UserRepositoryInterface $users,
        private EventSeatRepositoryInterface $eventSeats,
        private OrderRepositoryInterface $orders,
        private EventBusInterface $eventBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function reserve(Uuid $userId, array $eventSeatIds): void
    {
        for ($attempt = 1; $attempt <= self::MAX_CODE_GENERATION_ATTEMPTS; $attempt++) {
            try {
                $this->reserveOnce($userId, $eventSeatIds);

                return;
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === self::MAX_CODE_GENERATION_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    private function reserveOnce(Uuid $userId, array $eventSeatIds): void
    {
        $events = $this->transactionManager->transactional(function () use ($userId, $eventSeatIds): array {
            $user = $this->users->findById($userId);
            if (!$user) {
                throw new EntityNotFoundException('User not found.');
            }

            if ($eventSeatIds === []) {
                throw new BusinessRuleViolationException('No seats selected.');
            }

            if (count(array_unique($eventSeatIds)) !== count($eventSeatIds)) {
                throw new BusinessRuleViolationException('Duplicate seat ids are not allowed.');
            }

            $seats = $this->eventSeats->lockAndFindByIds($eventSeatIds);
            if (count($seats) !== count($eventSeatIds)) {
                throw new EntityNotFoundException('Some of the selected seats do not exist.');
            }

            foreach ($seats as $eventSeat) {
                if (!$eventSeat->isAvailable()) {
                    throw new BusinessRuleViolationException('Some seats are not available.');
                }
            }

            $order = Order::create($user);

            foreach ($seats as $eventSeat) {
                $eventSeat->reserve();
                $ticket = Ticket::create($order, $eventSeat, self::generateTicketCode());
                $order->addTicket($ticket);
            }

            $order->markSeatsAsReserved();

            $this->orders->save($order);

            return $order->releaseEvents();
        });

        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }

    private static function generateTicketCode(): TicketCode
    {
        return new TicketCode(sprintf('TKT-%s', strtoupper(bin2hex(random_bytes(4)))));
    }
}
