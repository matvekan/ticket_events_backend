<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Venue\ListVenuesQuery;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues', name: 'venue.list', methods: ['GET'])]
#[OA\Tag(name: 'Venues')]
#[OA\Get(
    path: '/api/venues',
    summary: 'List all venues',
    tags: ['Venues'],
    responses: [
        new OA\Response(response: 200, description: 'List of venues', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Venue'))),
    ]
)]
final class ListVenuesController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new ListVenuesQuery()));
    }
}
