<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Service\Venue\VenueService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AddSeatsHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueService $venueService,
    ) {
    }

    public function __invoke(AddSeatsToVenueCommand $command): void
    {
        $this->venueService->addSeats($command->venueId, $command->seats);
    }
}
