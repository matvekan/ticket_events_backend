<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\Event\ListEventsQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'event.list', methods: ['GET'])]
#[OA\Tag(name: 'Events')]
#[OA\Get(
    path: '/api/events',
    summary: 'List published events',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'page', description: 'Page number', in: 'query', schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'limit', description: 'Items per page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'List of events', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Event'))),
    ]
)]
final class ListEventsController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(#[MapQueryString] ListEventsQuery $query): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch($query));
    }
}
