<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders', name: 'order.reserve', methods: ['POST'])]
final class ReserveSeatsController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Normalize raw transport input before constructing the command so a
        // malformed payload becomes 400/422 instead of a TypeError (500).
        $payload = $request->toArray();
        $seatIds = $payload['seatIds'] ?? [];

        if (!is_array($seatIds)) {
            throw new BadRequestHttpException('seatIds must be an array of seat identifiers.');
        }

        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $this->security->getUser()->id()->toRfc4122(),
            eventSeatIds: $seatIds,
        ));

        return new JsonResponse(['message' => 'Seats reserved.'], JsonResponse::HTTP_CREATED);
    }
}
