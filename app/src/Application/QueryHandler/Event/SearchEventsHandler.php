<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\EventDto;
use App\Application\Dto\Factory\EventDtoFactory;
use App\Application\Query\Event\SearchEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class SearchEventsHandler implements QueryHandlerInterface
{
    private const INDEX = 'events';

    public function __construct(
        private readonly Client $elasticsearch,
        private readonly EventRepositoryInterface $events,
        private readonly EventDtoFactory $eventDtoFactory,
    ) {
    }

    /** @return EventDto[] */
    public function __invoke(SearchEventsQuery $query): array
    {
        try {
            $results = $this->elasticsearch
                ->search([
                    'index' => self::INDEX,
                    'body' => $this->buildQueryBody($query),
                ])
                ->asArray();
        } catch (\Throwable) {
            return $this->eventDtoFactory->fromEventList($this->events->findAllPublished());
        }

        return $this->mapHits($results);
    }

    private function buildQueryBody(SearchEventsQuery $query): array
    {
        $must = [];
        if ($query->query) {
            $must[] = [
                'multi_match' => [
                    'query' => $query->query,
                    'fields' => ['title^3', 'description', 'venue_name', 'venue_city'],
                ],
            ];
        }

        $filter = [['term' => ['status' => 'published']]];
        if ($query->city) {
            $filter[] = ['match' => ['venue_city' => $query->city]];
        }
        if ($query->dateFrom) {
            $filter[] = ['range' => ['date' => ['gte' => $query->dateFrom->format('c')]]];
        }
        if ($query->dateTo) {
            $filter[] = ['range' => ['date' => ['lte' => $query->dateTo->format('c')]]];
        }

        return [
            'query' => [
                'bool' => [
                    'must' => $must !== [] ? $must : ['match_all' => new \stdClass()],
                    'filter' => $filter,
                ],
            ],
            'from' => max(0, ($query->page - 1) * $query->limit),
            'size' => $query->limit,
        ];
    }

    /** @return EventDto[] */
    private function mapHits(array $results): array
    {
        $events = [];

        foreach ($results['hits']['hits'] ?? [] as $hit) {
            $source = $hit['_source'];
            $events[] = new EventDto(
                id: $hit['_id'],
                title: $source['title'],
                description: $source['description'],
                date: $source['date'],
                venueName: $source['venue_name'],
                venueCity: $source['venue_city'],
                priceMin: (float) $source['price_min'],
                priceMax: (float) $source['price_max'],
                status: $source['status'],
            );
        }

        return $events;
    }
}
