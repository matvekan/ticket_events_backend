<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Event\EventBusInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\Ticket;
use App\Domain\Event\SeatsReservedEvent;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class ReserveSeatsHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly EventSeatRepositoryInterface $eventSeatRepo,
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly EventBusInterface $eventBus,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ReserveSeatsCommand $command): void
    {
        $order = null;
        $userId = null;
        $eventSeatIds = [];

        $this->transactionManager->transactional(function () use ($command, &$order, &$userId, &$eventSeatIds): void {
            $user = $this->userRepo->findById($command->userId);
            if (!$user) {
                throw new \DomainException('User not found.');
            }

            $eventSeats = $this->eventSeatRepo->lockAndFindByIds($command->eventSeatIds);

            foreach ($eventSeats as $eventSeat) {
                if (!$eventSeat->isAvailable()) {
                    throw new \DomainException('Some seats are not available.');
                }
            }

            $order = new Order($user);

            foreach ($eventSeats as $eventSeat) {
                $eventSeat->reserve();
                $code = $this->generateTicketCode($eventSeat);
                $ticket = new Ticket($order, $eventSeat, $code);
                $order->addTicket($ticket);
            }

            $this->orderRepo->save($order);

            $userId = $user->getId();
            $eventSeatIds = array_map(fn (EventSeat $es): Uuid => $es->getId(), $eventSeats);
        });

        if ($order !== null && $userId !== null) {
            $this->eventBus->dispatch(new SeatsReservedEvent($order->getId(), $userId, $eventSeatIds));
        }
    }

    private function generateTicketCode(EventSeat $eventSeat): string
    {
        return sprintf('TKT-%s-%s', $eventSeat->getId()->toRfc4122(), bin2hex(random_bytes(4)));
    }
}
