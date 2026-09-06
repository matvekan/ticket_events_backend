<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Dto\SeatData;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues/{id}/seats', name: 'venue.add_seats', methods: ['POST'])]
#[OA\Tag(name: 'Venues')]
#[OA\Parameter(name: 'id', description: 'Venue UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Post(
    path: '/api/venues/{id}/seats',
    summary: 'Add seats to a venue',
    tags: ['Venues'],
    security: [['BearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['seats'],
            properties: [
                new OA\Property(property: 'seats', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'row', type: 'string', example: 'A'),
                        new OA\Property(property: 'number', type: 'integer', example: 1),
                        new OA\Property(property: 'type', type: 'string', enum: ['standard', 'vip', 'premium'], example: 'standard'),
                        new OA\Property(property: 'sector', type: 'string', example: 'Main Hall'),
                    ]
                )),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Seats added'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Venue not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
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
            fn (array $seat): SeatData => new SeatData(
                row: $seat['row'],
                number: (int) $seat['number'],
                type: $seat['type'],
                sector: $seat['sector'] ?? null,
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
