<?php

declare(strict_types=1);

namespace App\Application\Service\Venue;

use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Seat;
use App\Domain\Entity\Venue;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use Symfony\Component\Uid\Uuid;

final readonly class VenueService
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private SeatRepositoryInterface $seats,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function create(string $name, string $address, string $city, ?float $latitude = null, ?float $longitude = null): void
    {
        $this->transactionManager->transactional(function () use ($name, $address, $city, $latitude, $longitude): void {
            $venue = Venue::create(new VenueName($name), new VenueAddress($address), new VenueCity($city), $latitude, $longitude);
            $this->venues->save($venue);
        });
    }

    public function addSeats(Uuid $venueId, array $seatsData): void
    {
        $this->transactionManager->transactional(function () use ($venueId, $seatsData): void {
            $venue = $this->venues->findById($venueId);
            if (!$venue) {
                throw new EntityNotFoundException('Venue not found.');
            }

            if ($seatsData === []) {
                throw new BusinessRuleViolationException('No seats provided.');
            }

            $existing = $this->seats->findByVenueId($venueId);
            $occupied = [];
            foreach ($existing as $seat) {
                $occupied[(string) $seat->row() . '/' . $seat->number()->toValue()] = true;
            }

            $seats = [];

            foreach ($seatsData as $seatData) {
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
