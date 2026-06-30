<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Domain\Entity\Seat;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\SeatType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AddSeatsHandler implements CommandHandlerInterface
{
    private const array VALID_TYPES = ['standard', 'vip', 'premium'];

    public function __construct(
        private readonly VenueRepositoryInterface $venueRepo,
        private readonly SeatRepositoryInterface $seatRepo,
    ) {
    }

    public function __invoke(AddSeatsToVenueCommand $command): void
    {
        $venue = $this->venueRepo->findById($command->venueId);
        if (!$venue) {
            throw new \DomainException('Venue not found.');
        }

        foreach ($command->seats as $seatData) {
            if (!in_array($seatData['type'], self::VALID_TYPES, true)) {
                throw new \DomainException(sprintf('Invalid seat type "%s". Allowed: standard, vip, premium.', $seatData['type']));
            }

            $seat = new Seat(
                $venue,
                $seatData['row'],
                $seatData['number'],
                SeatType::from($seatData['type']),
                $seatData['sector'] ?? null,
            );
            $this->seatRepo->save($seat);
        }
    }
}
