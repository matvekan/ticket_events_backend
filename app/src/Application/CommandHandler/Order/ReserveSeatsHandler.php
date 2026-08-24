<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Order;
use App\Domain\Entity\Ticket;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\UserId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ReserveSeatsHandler implements CommandHandlerInterface
{
    private const MAX_CODE_GENERATION_ATTEMPTS = 3;

    public function __construct(
        private UserRepositoryInterface $users,
        private EventSeatRepositoryInterface $eventSeats,
        private OrderRepositoryInterface $orders,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function __invoke(ReserveSeatsCommand $command): void
    {
        $userId = new UserId($command->userId()->toRfc4122());
        $eventSeatIds = array_map(
            static fn (Uuid $id): EventSeatId => new EventSeatId($id->toRfc4122()),
            $command->eventSeatIds(),
        );

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

    /** @param EventSeatId[] $eventSeatIds */
    private function reserveOnce(UserId $userId, array $eventSeatIds): void
    {
        $this->transactionManager->transactional(function () use ($userId, $eventSeatIds): array {
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

            $firstEvent = $seats[0]->event();
            $firstEventId = $firstEvent->id()->toString();
            foreach ($seats as $eventSeat) {
                if ($eventSeat->event()->id()->toString() !== $firstEventId) {
                    throw new BusinessRuleViolationException('All seats must belong to the same event.');
                }
            }

            if ($firstEvent->status() !== EventStatus::Published) {
                throw new BusinessRuleViolationException('Event is not published.');
            }

            if ($firstEvent->date() <= $this->clock->now()) {
                throw new BusinessRuleViolationException('Event has already occurred or is in the past.');
            }

            foreach ($seats as $eventSeat) {
                if (!$eventSeat->isAvailable()) {
                    throw new BusinessRuleViolationException('Some seats are not available.');
                }
            }

            $order = Order::create($user->id(), $this->clock, $this->ids);

            foreach ($seats as $eventSeat) {
                $eventSeat->reserve();
                $ticket = Ticket::create($order, $eventSeat, $this->generateTicketCode());
                $order->addTicket($ticket);
            }

            $order->markSeatsAsReserved();

            $this->orders->save($order);

            return $order->releaseEvents();
        });
    }

    private function generateTicketCode(): TicketCode
    {
        // IdGenerator provides random bytes hex via generate() - use last 8 chars for ticket code
        return new TicketCode(sprintf('TKT-%s', strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))));
    }
}