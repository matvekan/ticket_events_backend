<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Venue\GetVenueSeatingQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/venues/{id}/seats', name: 'venue.seating', methods: ['GET'])]
final class GetVenueSeatingController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetVenueSeatingQuery(Uuid::fromRfc4122($id))));
    }
}
