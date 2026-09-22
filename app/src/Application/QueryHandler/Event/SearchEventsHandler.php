<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\Factory\EventDtoFactory;
use App\Application\Query\Event\SearchEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventSearchInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class SearchEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private EventSearchInterface $eventSearch,
        private EventDtoFactory $eventDtoFactory,
    ) {
    }

    /**
     * @return array{items: array<int, \App\Application\Dto\EventDto>, nextCursor: ?string}
     */
    public function __invoke(SearchEventsQuery $query): array
    {
        $dtos = $this->eventSearch->search(
            $query->query,
            $query->city,
            $query->dateFrom,
            $query->dateTo,
            $query->limit + 1,
            $query->cursor,
        );

        return $this->eventDtoFactory->createCursorPaginatedResponseFromDtos($dtos, $query->limit);
    }
}
