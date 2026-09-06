<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Event\PublishEventCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events/{id}/publish', name: 'event.publish', methods: ['POST'])]
#[OA\Tag(name: 'Events')]
#[OA\Parameter(name: 'id', description: 'Event UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Post(
    path: '/api/events/{id}/publish',
    summary: 'Publish an event',
    tags: ['Events'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Event published'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Event not found'),
        new OA\Response(response: 422, description: 'Validation failed or event cannot be published'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class PublishEventController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new PublishEventCommand($id));

        return new JsonResponse(['message' => 'Event published.']);
    }
}
