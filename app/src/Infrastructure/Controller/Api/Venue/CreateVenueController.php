<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Venue\CreateVenueCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues', name: 'venue.create', methods: ['POST'])]
#[OA\Tag(name: 'Venues')]
#[OA\Post(
    path: '/api/venues',
    summary: 'Create a new venue',
    tags: ['Venues'],
    security: [['BearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'address', 'city', 'latitude', 'longitude'],
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Grand Concert Hall'),
                new OA\Property(property: 'address', type: 'string', example: 'Main Street, 123'),
                new OA\Property(property: 'city', type: 'string', example: 'Minsk'),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 53.9),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 27.56),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Venue created'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation failed'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class CreateVenueController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] CreateVenueCommand $command): JsonResponse
    {
        $this->commandBus->dispatch($command);

        return new JsonResponse(['message' => 'Venue created.'], JsonResponse::HTTP_CREATED);
    }
}
