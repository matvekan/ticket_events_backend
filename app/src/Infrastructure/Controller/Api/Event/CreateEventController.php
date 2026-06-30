<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Event\CreateEventCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'event.create', methods: ['POST'])]
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
