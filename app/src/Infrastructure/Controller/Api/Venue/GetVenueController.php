<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Venue\GetVenueQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues/{id}', name: 'venue.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
#[OA\Tag(name: 'Venues')]
#[OA\Parameter(name: 'id', description: 'Venue UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Get(
    path: '/api/venues/{id}',
    summary: 'Get venue details',
    tags: ['Venues'],
    responses: [
        new OA\Response(response: 200, description: 'Venue details', content: new OA\JsonContent(ref: '#/components/schemas/Venue')),
        new OA\Response(response: 404, description: 'Venue not found'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class GetVenueController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $venue = $this->queryBus->dispatch(new GetVenueQuery($id));
        if ($venue === null) {
            return new JsonResponse(['error' => 'Venue not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($venue);
    }
}
