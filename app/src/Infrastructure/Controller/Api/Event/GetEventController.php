<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\Event\GetEventDetailsQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/events/{id}', name: 'event.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
final class GetEventController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $event = $this->queryBus->dispatch(new GetEventDetailsQuery(Uuid::fromRfc4122($id)));
        if ($event === null) {
            return new JsonResponse(['error' => 'Event not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($event);
    }
}
