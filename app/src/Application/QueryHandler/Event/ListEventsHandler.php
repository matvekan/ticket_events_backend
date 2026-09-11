<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Query\Event\ListEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
    ) {
    }

    public function __invoke(ListEventsQuery $query): array
    {
        return $this->events->findPublishedList($query->limit, ($query->page - 1) * $query->limit);
    }
}
