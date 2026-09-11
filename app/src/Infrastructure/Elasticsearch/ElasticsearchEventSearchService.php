<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use App\Application\Dto\EventDto;
use App\Application\Dto\Factory\EventDtoFactory;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSearchInterface;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class ElasticsearchEventSearchService implements EventSearchInterface
{
    private const INDEX = 'events';

    public function __construct(
        private readonly Client $elasticsearch,
        private readonly EventRepositoryInterface $events,
        private readonly EventDtoFactory $eventDtoFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function search(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        int $offset,
    ): array {
        try {
            $results = $this->elasticsearch
                ->search([
                    'index' => self::INDEX,
                    'body' => $this->buildQueryBody($query, $city, $dateFrom, $dateTo, $limit, $offset),
                ])
                ->asArray();
        } catch (\Throwable $e) {
            $this->logger->warning('Elasticsearch search failed, falling back to DB: {error}', ['error' => $e->getMessage()]);

            $events = $this->events->searchPublished($query, $city, $dateFrom, $dateTo, $limit, $offset);

            return $this->eventDtoFactory->fromEventList($events);
        }

        return $this->mapHits($results);
    }

    private function buildQueryBody(?string $query, ?string $city, ?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateTo, int $limit, int $offset): array
    {
        $must = [];
        if ($query) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['title^3', 'description', 'venue_name', 'venue_city'],
                ],
            ];
        }

        $filter = [['term' => ['status' => 'published']]];
        if ($city) {
            $filter[] = ['match' => ['venue_city' => $city]];
        }
        if ($dateFrom) {
            $filter[] = ['range' => ['date' => ['gte' => $dateFrom->format('c')]]];
        }
        if ($dateTo) {
            $filter[] = ['range' => ['date' => ['lte' => $dateTo->format('c')]]];
        }

        return [
            'query' => [
                'bool' => [
                    'must' => $must !== [] ? $must : ['match_all' => new \stdClass()],
                    'filter' => $filter,
                ],
            ],
            'from' => $offset,
            'size' => $limit,
        ];
    }

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
                priceMin: (int) $source['price_min'],
                priceMax: (int) $source['price_max'],
                priceCurrency: $source['price_currency'] ?? 'BYN',
                status: $source['status'],
            );
        }

        return $events;
    }
}
