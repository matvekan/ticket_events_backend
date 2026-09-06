<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Seat\GetAvailableSeatsQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events/{id}/seats', name: 'event.available_seats', methods: ['GET'])]
#[OA\Tag(name: 'Events')]
#[OA\Parameter(name: 'id', description: 'Event UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Get(
    path: '/api/events/{id}/seats',
    summary: 'Get available seats for an event',
    tags: ['Events'],
    responses: [
        new OA\Response(response: 200, description: 'List of available seats', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Seat'))),
        new OA\Response(response: 404, description: 'Event not found'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class GetAvailableSeatsController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetAvailableSeatsQuery($id)));
    }
}
