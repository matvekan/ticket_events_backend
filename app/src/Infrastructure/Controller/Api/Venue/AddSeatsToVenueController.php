<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/venues/{id}/seats', name: 'venue.add_seats', methods: ['POST'])]
final class AddSeatsToVenueController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $this->commandBus->dispatch(new AddSeatsToVenueCommand(
            venueId: Uuid::fromRfc4122($id),
            seats: $data['seats'] ?? [],
        ));

        return new JsonResponse(['message' => 'Seats added.'], JsonResponse::HTTP_CREATED);
    }
}
