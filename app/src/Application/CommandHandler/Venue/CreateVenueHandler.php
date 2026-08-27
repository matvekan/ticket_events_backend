<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Venue;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Venue;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateVenueHandler implements CommandHandlerInterface
{
    public function __construct(
        private VenueRepositoryInterface $venues,
        private TransactionManagerInterface $transactionManager,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function __invoke(CreateVenueCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $venue = Venue::create(
                new VenueName($command->name),
                new VenueAddress($command->address),
                new VenueCity($command->city),
                $this->ids,
                $command->latitude,
                $command->longitude,
            );

            $this->venues->save($venue);
        });
    }
}
