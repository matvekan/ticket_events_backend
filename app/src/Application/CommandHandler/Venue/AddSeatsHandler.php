<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Seat;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class AddSeatsHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private SeatRepositoryInterface $seats,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(AddSeatsToVenueCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $venue = $this->venues->findById(Uuid::fromString($command->venueId));
            if (!$venue) {
                throw new EntityNotFoundException('Venue not found.');
            }

            if ($command->seats === []) {
                throw new BusinessRuleViolationException('No seats provided.');
            }

            $occupied = [];
            foreach ($this->seats->findByVenueId($venue->id()) as $existingSeat) {
                $occupied[(string) $existingSeat->row() . '/' . $existingSeat->number()->toValue()] = true;
            }

            $seats = [];

            foreach ($command->seats as $seatData) {
                $position = $seatData->row . '/' . $seatData->number;
                if (isset($occupied[$position])) {
                    throw new BusinessRuleViolationException('Seat with row and number already exists.');
                }

                $seats[] = Seat::create(
                    $venue,
                    new SeatRow($seatData->row),
                    new SeatNumber($seatData->number),
                    SeatType::from($seatData->type),
                    $seatData->sector !== null ? new SeatSector($seatData->sector) : null,
                );

                $occupied[$position] = true;
            }

            $this->seats->saveAll($seats);
        });
    }
}