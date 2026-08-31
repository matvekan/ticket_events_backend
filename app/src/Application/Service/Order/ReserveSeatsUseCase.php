<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Exception\PersistenceConstraintViolationException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\Shared\Service\OrderTicketFactoryInterface;
use App\Domain\Shared\Service\SeatSelectionValidatorInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\UserId;

final readonly class ReserveSeatsUseCase implements ReserveSeatsUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private EventSeatRepositoryInterface $eventSeats,
        private OrderRepositoryInterface $orders,
        private TransactionManagerInterface $transactionManager,
        private SeatAvailabilityCacheInterface $seatAvailabilityCache,
        private SeatSelectionValidatorInterface $seatValidator,
        private OrderTicketFactoryInterface $orderFactory,
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
    ) {}

    /**
     * @param EventSeatId[] $seatIds
     */
    public function execute(UserId $userId, array $seatIds): void
    {
        $affectedEventIds = [];

        try {
            $this->transactionManager->transactional(
                function () use ($userId, $seatIds, &$affectedEventIds): void {
                    $user = $this->users->findById($userId);
                    if (!$user) {
                        throw new EntityNotFoundException('User not found.');
                    }

                    $seats = $this->eventSeats->lockAndFindByIds($seatIds);
                    if (count($seats) !== count($seatIds)) {
                        throw new EntityNotFoundException('Some of the selected seats do not exist.');
                    }

                    $this->seatValidator->validate($seats, $this->clock);

                    $order = $this->orderFactory->create(
                        $user->id(),
                        $seats,
                        $this->clock,
                        $this->ids,
                    );

                    $this->orders->save($order);

                    $affectedEventIds[] = $seats[0]->event()->id()->toString();
                }
            );
        } catch (PersistenceConstraintViolationException) {
            throw new BusinessRuleViolationException('Some seats are no longer available.');
        }

        foreach (array_unique($affectedEventIds) as $eventId) {
            $this->seatAvailabilityCache->invalidate($eventId);
        }
    }
}