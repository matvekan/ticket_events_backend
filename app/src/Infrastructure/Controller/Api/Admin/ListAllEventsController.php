<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Event\ListAllEventsQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/events', name: 'admin.events.list', methods: ['GET'])]
#[OA\Tag(name: 'Admin')]
#[OA\Get(
    path: '/api/admin/events',
    summary: 'List all events (admin)',
    security: [['BearerAuth' => []]],
    tags: ['Admin'],
    responses: [
        new OA\Response(response: 200, description: 'List of all events', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/EventDetails'))),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final readonly class ListAllEventsController
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    public function __invoke(#[MapQueryString] ?ListAllEventsQuery $query = null): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch($query ?? new ListAllEventsQuery()));
    }
}
