<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\Event\GetEventDetailsQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events/{id}', name: 'event.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
#[OA\Tag(name: 'Events')]
#[OA\Get(
    path: '/api/events/{id}',
    summary: 'Get event details',
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'id', description: 'Event UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Event details', content: new OA\JsonContent(ref: '#/components/schemas/EventDetails')),
        new OA\Response(response: 404, description: 'Event not found'),
    ]
)]
final class GetEventController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $event = $this->queryBus->dispatch(new GetEventDetailsQuery($id));
        if ($event === null) {
            return new JsonResponse(['error' => 'Event not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($event);
    }
}
