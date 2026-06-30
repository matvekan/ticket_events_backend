<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\QueryBusInterface;
use App\Application\Query\Seat\GetAvailableSeatsQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/events/{id}/seats', name: 'event.available_seats', methods: ['GET'])]
final class GetAvailableSeatsController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetAvailableSeatsQuery(Uuid::fromRfc4122($id))));
    }
}
