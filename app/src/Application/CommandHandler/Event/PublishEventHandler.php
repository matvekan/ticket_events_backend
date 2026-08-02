<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Service\Event\EventService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventService $eventService,
    ) {
    }

    public function __invoke(PublishEventCommand $command): void
    {
        $this->eventService->publish($command->eventId);
    }
}
