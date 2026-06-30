<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\EventDto;
use App\Application\Dto\Factory\EventDtoFactory;
use App\Application\Query\Event\ListEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepo,
        private readonly EventDtoFactory $eventDtoFactory,
    ) {
    }

    /** @return EventDto[] */
    public function __invoke(ListEventsQuery $query): array
    {
        $events = $this->eventRepo->findAllPublished();

        return $this->eventDtoFactory->fromEventList($events);
    }
}
