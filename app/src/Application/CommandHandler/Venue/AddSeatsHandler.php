<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Dto\SeatData;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Seat;
use App\Domain\Entity\Venue;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\VenueId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AddSeatsHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private SeatRepositoryInterface $seats,
        private TransactionManagerInterface $transactionManager,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function __invoke(AddSeatsToVenueCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $venue = $this->findVenue($command);

            $occupied = $this->existingPositions($venue);
            $seats = array_map(
                fn (SeatData $data): Seat => $this->createSeat($venue, $data, $occupied),
                $command->seats,
            );

            $this->seats->saveAll($seats);
        });
    }

    private function findVenue(AddSeatsToVenueCommand $command): Venue
    {
        $venue = $this->venues->findById(new VenueId($command->venueId));
        if (!$venue) {
            throw new EntityNotFoundException('Venue not found.');
        }

        return $venue;
    }

    /**
     * @return array<string, bool>
     */
    private function existingPositions(Venue $venue): array
    {
        $positions = [];
        foreach ($this->seats->findByVenueId($venue->id()) as $seat) {
            $positions[$this->positionKey((string) $seat->row(), $seat->number()->toValue())] = true;
        }

        return $positions;
    }

    /**
     * @param array<string, bool> $occupied
     */
    private function createSeat(Venue $venue, SeatData $data, array &$occupied): Seat
    {
        $key = $this->positionKey($data->row, $data->number);
        if (isset($occupied[$key])) {
            throw new BusinessRuleViolationException('Seat with row and number already exists.');
        }
        $occupied[$key] = true;

        return Seat::create(
            $venue->id(),
            new SeatRow($data->row),
            new SeatNumber($data->number),
            SeatType::from($data->type),
            $this->ids,
            $data->sector !== null ? new SeatSector($data->sector) : null,
        );
    }

    private function positionKey(string $row, string|int $number): string
    {
        return \sprintf('%s/%s', $row, $number);
    }
}
