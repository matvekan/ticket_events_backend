<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Service\Venue\VenueService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateVenueHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueService $venueService,
    ) {
    }

    public function __invoke(CreateVenueCommand $command): void
    {
        $this->venueService->create($command->name, $command->address, $command->city, $command->latitude, $command->longitude);
    }
}
