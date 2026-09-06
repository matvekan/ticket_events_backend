<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\Event\SearchEventsQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events/search', name: 'event.search', methods: ['GET'])]
#[OA\Tag(name: 'Events')]
#[OA\Get(
    path: '/api/events/search',
    summary: 'Search events with filters',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'query', description: 'Search query (title/description)', in: 'query', schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 255)),
        new OA\Parameter(name: 'city', description: 'Filter by city', in: 'query', schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 100)),
        new OA\Parameter(name: 'dateFrom', description: 'Filter events from date (ISO 8601)', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
        new OA\Parameter(name: 'dateTo', description: 'Filter events to date (ISO 8601)', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
        new OA\Parameter(name: 'page', description: 'Page number', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'limit', description: 'Items per page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'List of events', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Event'))),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class SearchEventsController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(#[MapQueryString] SearchEventsQuery $query): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch($query));
    }
}
