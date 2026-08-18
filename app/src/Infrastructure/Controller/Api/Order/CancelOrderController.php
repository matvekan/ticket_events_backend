<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\CancelOrderCommand;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/{id}/cancel', name: 'order.cancel', methods: ['POST'])]
final class CancelOrderController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new CancelOrderCommand(
            orderId: $id,
            userId: $this->security->getUser()?->id()?->toRfc4122(),
        ));

        return new JsonResponse(['message' => 'Order cancelled.']);
    }
}
