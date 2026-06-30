<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Venue\GetVenueQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/venues/{id}', name: 'venue.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
final class GetVenueController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $venue = $this->queryBus->dispatch(new GetVenueQuery(Uuid::fromRfc4122($id)));
        if ($venue === null) {
            return new JsonResponse(['error' => 'Venue not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($venue);
    }
}
