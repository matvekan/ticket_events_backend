<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\EventDetailsDto;
use App\Application\Dto\Factory\EventDtoFactory;
use App\Application\Query\Event\GetEventDetailsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetEventDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepo,
        private readonly EventDtoFactory $eventDtoFactory,
    ) {
    }

    public function __invoke(GetEventDetailsQuery $query): ?EventDetailsDto
    {
        $event = $this->eventRepo->findById($query->eventId);
        if (!$event) {
            return null;
        }

        return $this->eventDtoFactory->fromEventDetails($event);
    }
}
