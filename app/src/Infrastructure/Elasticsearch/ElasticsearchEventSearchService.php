<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use App\Application\Dto\EventDto;
use App\Application\Dto\Factory\EventDtoFactory;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSearchInterface;
use DateTimeImmutable;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

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

    /**
     * @return array<int, EventDto>
     */
    public function search(
        ?string $query,
        ?string $city,
        ?DateTimeImmutable $dateFrom,
        ?DateTimeImmutable $dateTo,
        int $limit,
        ?string $cursor,
    ): array {
        try {
            $response = $this->elasticsearch
                ->search([
                    'index' => self::INDEX,
                    'body' => $this->buildQueryBody($query, $city, $dateFrom, $dateTo, $limit, $cursor),
                ]);
            assert(method_exists($response, 'asArray'));
            /** @var array<string, mixed> $results */
            $results = $response->asArray();
        } catch (Throwable $e) {
            $this->logger->warning('Elasticsearch search failed, falling back to DB: {error}', ['error' => $e->getMessage()]);
            $events = $this->events->searchPublished($query, $city, $dateFrom, $dateTo, $limit, $cursor);

            return $this->eventDtoFactory->fromEventList($events);
        }

        return $this->mapHits($results);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQueryBody(?string $query, ?string $city, ?DateTimeImmutable $dateFrom, ?DateTimeImmutable $dateTo, int $limit, ?string $cursor): array
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

        $body = [
            'query' => [
                'bool' => [
                    'must' => $must !== [] ? $must : ['match_all' => new stdClass()],
                    'filter' => $filter,
                ],
            ],
            'size' => $limit,
            'sort' => [
                ['date' => 'desc'],
                ['id' => 'desc'],
            ],
        ];

        if ($cursor) {
            $decoded = base64_decode($cursor, true);
            if ($decoded === false) {
                return $body;
            }
            $parts = explode('|', $decoded);
            if (\count($parts) === 2) {
                $body['search_after'] = [$parts[0], $parts[1]];
            }
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $results
     * @return array<int, EventDto>
     */
    private function mapHits(array $results): array
    {
        $events = [];

        $hitsWrapper = $results['hits'] ?? [];
        assert(is_array($hitsWrapper));

        $hits = $hitsWrapper['hits'] ?? [];
        assert(is_array($hits));

        foreach ($hits as $hit) {
            assert(is_array($hit));

            $source = $hit['_source'] ?? [];
            assert(is_array($source));

            $id = $hit['_id'] ?? '';
            $title = $source['title'] ?? '';
            $description = $source['description'] ?? '';
            $date = $source['date'] ?? '';
            $venueName = $source['venue_name'] ?? '';
            $venueCity = $source['venue_city'] ?? '';
            $priceMin = $source['price_min'] ?? 0;
            $priceMax = $source['price_max'] ?? 0;
            $priceCurrency = $source['price_currency'] ?? 'BYN';
            $status = $source['status'] ?? '';
            assert(is_scalar($id));
            assert(is_scalar($title));
            assert(is_scalar($description));
            assert(is_scalar($date));
            assert(is_scalar($venueName));
            assert(is_scalar($venueCity));
            assert(is_scalar($priceMin));
            assert(is_scalar($priceMax));
            assert(is_scalar($priceCurrency));
            assert(is_scalar($status));

            $events[] = new EventDto(
                id: (string) $id,
                title: (string) $title,
                description: (string) $description,
                date: (string) $date,
                venueName: (string) $venueName,
                venueCity: (string) $venueCity,
                priceMin: (int) $priceMin,
                priceMax: (int) $priceMax,
                priceCurrency: (string) $priceCurrency,
                status: (string) $status,
            );
        }

        return $events;
    }
}
