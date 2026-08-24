<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\EventDto;
use App\Application\Port\EventReadRepositoryInterface;
use App\Application\Query\Event\ListEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventReadRepositoryInterface $events,
    ) {
    }

    /** @return EventDto[] */
    public function __invoke(ListEventsQuery $query): array
    {
        return $this->events->findPublished($query->limit, ($query->page - 1) * $query->limit);
    }
}
