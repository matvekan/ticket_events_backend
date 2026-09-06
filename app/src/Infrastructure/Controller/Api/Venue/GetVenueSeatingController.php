<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Venue\GetVenueSeatingQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues/{id}/seats', name: 'venue.seating', methods: ['GET'])]
#[OA\Tag(name: 'Venues')]
#[OA\Parameter(name: 'id', description: 'Venue UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Get(
    path: '/api/venues/{id}/seats',
    summary: 'Get venue seating plan',
    tags: ['Venues'],
    responses: [
        new OA\Response(response: 200, description: 'Venue seating plan', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Seat'))),
        new OA\Response(response: 404, description: 'Venue not found'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class GetVenueSeatingController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetVenueSeatingQuery($id)));
    }
}
