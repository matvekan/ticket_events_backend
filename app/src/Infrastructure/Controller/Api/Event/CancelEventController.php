<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Command\Event\CancelEventCommand;
use App\Application\Command\CommandBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/events/{id}/cancel', name: 'event.cancel', methods: ['POST'])]
final class CancelEventController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new CancelEventCommand(Uuid::fromRfc4122($id)));

        return new JsonResponse(['message' => 'Event cancelled.']);
    }
}
