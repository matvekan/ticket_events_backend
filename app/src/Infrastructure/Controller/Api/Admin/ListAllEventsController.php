<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Event\ListAllEventsQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/events', name: 'admin.events.list', methods: ['GET'])]
#[OA\Tag(name: 'Admin')]
#[OA\Get(
    path: '/api/admin/events',
    summary: 'List all events (admin)',
    tags: ['Admin'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of all events', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/EventDetails'))),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
final class ListAllEventsController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new ListAllEventsQuery()));
    }
}
