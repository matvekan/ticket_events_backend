<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Event\CreateEventCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'event.create', methods: ['POST'])]
#[OA\Tag(name: 'Events')]
#[OA\Post(
    path: '/api/events',
    summary: 'Create a new event',
    tags: ['Events'],
    security: [['BearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'description', 'date', 'venueId', 'seats'],
            properties: [
                new OA\Property(property: 'title', type: 'string', maxLength: 100, example: 'Rock Symphony Night'),
                new OA\Property(property: 'description', type: 'string', maxLength: 500, example: 'An amazing evening of rock music...'),
                new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2026-12-31T20:00:00+03:00'),
                new OA\Property(property: 'venueId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'seats', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'seatId', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'priceAmount', type: 'integer', example: 5000),
                    ]
                )),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Event created'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation failed'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class CreateEventController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] CreateEventCommand $command): JsonResponse
    {
        $this->commandBus->dispatch($command);

        return new JsonResponse(['message' => 'Event created.'], JsonResponse::HTTP_CREATED);
    }
}
