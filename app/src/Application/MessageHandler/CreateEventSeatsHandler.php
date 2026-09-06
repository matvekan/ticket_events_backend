<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\CreateEventSeatsMessage;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\EventSeat;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\SeatId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateEventSeatsHandler
{
    public function __construct(
        private EventRepositoryInterface $events,
        private SeatRepositoryInterface $seats,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function __invoke(CreateEventSeatsMessage $message): void
    {
        $this->transactionManager->transactional(function () use ($message): void {
            $event = $this->events->findById(new EventId($message->eventId));
            if (!$event) {
                return;
            }

            $seatIds = array_map(
                static fn ($seatData) => new SeatId(is_array($seatData) ? $seatData['seatId'] : $seatData->seatId),
                $message->seatsData
            );

            $seats = $this->seats->findByIds($seatIds);

            $seatById = [];
            foreach ($seats as $seat) {
                $seatById[$seat->rawId()] = $seat;
            }

            foreach ($message->seatsData as $seatData) {
                $seatIdStr = is_array($seatData) ? $seatData['seatId'] : $seatData->seatId;
                $priceAmount = is_array($seatData) ? $seatData['priceAmount'] : $seatData->priceAmount;

                $seat = $seatById[$seatIdStr] ?? null;

                if (!$seat || $seat->venueId()->toString() !== $message->venueId) {
                    continue;
                }

                $eventSeat = EventSeat::create($event, $seat, $priceAmount, $this->ids);
                $this->eventSeats->save($eventSeat);
            }
        });
    }
}
