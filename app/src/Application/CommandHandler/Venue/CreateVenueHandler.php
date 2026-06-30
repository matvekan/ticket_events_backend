<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Domain\Entity\Venue;
use App\Domain\Repository\VenueRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateVenueHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly VenueRepositoryInterface $venueRepo,
    ) {
    }

    public function __invoke(CreateVenueCommand $command): void
    {
        $venue = new Venue($command->name, $command->address, $command->city);
        $this->venueRepo->save($venue);
    }
}
