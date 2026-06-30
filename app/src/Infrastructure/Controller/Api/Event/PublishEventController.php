<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Event\PublishEventCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/events/{id}/publish', name: 'event.publish', methods: ['POST'])]
final class PublishEventController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new PublishEventCommand(Uuid::fromRfc4122($id)));

        return new JsonResponse(['message' => 'Event published.']);
    }
}
