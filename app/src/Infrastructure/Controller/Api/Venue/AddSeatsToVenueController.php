<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Dto\SeatData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues/{id}/seats', name: 'venue.add_seats', methods: ['POST'])]
final class AddSeatsToVenueController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        string $id,
        #[MapRequestPayload] AddSeatsRequest $payload,
    ): JsonResponse {
        $seats = array_map(
            fn (AddSeatRequest $seat): SeatData => new SeatData(
                row: $seat->row,
                number: $seat->number,
                type: $seat->type,
                sector: $seat->sector,
            ),
            $payload->seats,
        );

        $this->commandBus->dispatch(new AddSeatsToVenueCommand(
            venueId: $id,
            seats: $seats,
        ));

        return new JsonResponse(['message' => 'Seats added.'], JsonResponse::HTTP_CREATED);
    }
}