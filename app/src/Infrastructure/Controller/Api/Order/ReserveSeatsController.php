<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders', name: 'order.reserve', methods: ['POST'])]
final class ReserveSeatsController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Single-parameter payload parsed directly from the request - no wrapper DTO needed.
        // Validation lives on the command (enforced by command bus middleware).
        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $this->security->getUser()->id()->toRfc4122(),
            eventSeatIds: $request->toArray()['seatIds'] ?? [],
        ));

        return new JsonResponse(['message' => 'Seats reserved.'], JsonResponse::HTTP_CREATED);
    }
}
