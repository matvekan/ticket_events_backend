<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CreateEventCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\VenueId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private EventRepositoryInterface $events,
        private SeatRepositoryInterface $seats,
        private EventSeatRepositoryInterface $eventSeats,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function __invoke(CreateEventCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $venue = $this->venues->findById(new VenueId($command->venueId));
            if (!$venue) {
                throw new EntityNotFoundException('Venue not found.');
            }

            $seatIds = array_map(static fn (array $s): SeatId => new SeatId($s['seatId']), $command->seats);

            if (count(array_unique(array_map(fn($id) => $id->toString(), $seatIds))) !== count($seatIds)) {
                throw new BusinessRuleViolationException('Duplicate seats in request.');
            }

            $seats = $this->seats->findByIds($seatIds);
            if (count($seats) !== count($seatIds)) {
                throw new BusinessRuleViolationException('Some of the specified seats do not exist.');
            }

            $seatById = [];
            foreach ($seats as $seat) {
                if ($seat->venueId()->toString() !== $command->venueId) {
                    throw new BusinessRuleViolationException('All seats must belong to the selected venue.');
                }
                $seatById[$seat->rawId()] = $seat;
            }

            $event = Event::create(
                new EventTitle($command->title),
                new EventDescription($command->description),
                $command->date,
                $venue,
                $this->clock,
                $this->ids,
            );
            $this->events->save($event);

            foreach ($command->seats as $seatData) {
                $seat = $seatById[$seatData['seatId']];
                $eventSeat = EventSeat::create($event, $seat, $seatData['priceAmount'], $this->ids);
                $this->eventSeats->save($eventSeat);
            }
        });
    }
}
