<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\Factory\EventDtoFactory;
use App\Application\Query\Event\ListEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private EventRepositoryInterface $events,
        private EventDtoFactory $eventDtoFactory,
    ) {
    }

    /**
     * @return array{items: array<int, \App\Application\Dto\EventDto>, nextCursor: ?string}
     */
    public function __invoke(ListEventsQuery $query): array
    {
        $events = $this->events->findPublishedCursor($query->limit + 1, $query->cursor);

        return $this->eventDtoFactory->createCursorPaginatedResponse($events, $query->limit);
    }
}
