<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

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
        $data = $request->toArray();

        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $this->security->getUser()->getId(),
            eventSeatIds: array_map(fn (string $id): Uuid => Uuid::fromRfc4122($id), $data['seatIds'] ?? []),
        ));

        return new JsonResponse(['message' => 'Seats reserved.'], JsonResponse::HTTP_CREATED);
    }
}
