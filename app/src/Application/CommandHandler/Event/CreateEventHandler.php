<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CreateEventCommand;
use App\Application\Service\Event\EventService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventService $eventService,
    ) {
    }

    public function __invoke(CreateEventCommand $command): void
    {
        $this->eventService->create(
            $command->title,
            $command->description,
            $command->date,
            $command->venueId,
            $command->seats,
        );
    }
}
