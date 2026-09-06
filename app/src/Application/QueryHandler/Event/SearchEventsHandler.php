<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Domain\Repository\EventSearchInterface;
use App\Application\Query\Event\SearchEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventSearchInterface $eventSearch,
    ) {
    }


    public function __invoke(SearchEventsQuery $query): array
    {
        return $this->eventSearch->search(
            $query->query,
            $query->city,
            $query->dateFrom,
            $query->dateTo,
            $query->limit,
            max(0, ($query->page - 1) * $query->limit),
        );
    }
}
