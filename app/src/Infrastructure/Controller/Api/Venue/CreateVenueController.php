<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Venue\CreateVenueCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues', name: 'venue.create', methods: ['POST'])]
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
