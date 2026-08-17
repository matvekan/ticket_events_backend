<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/orders/{id}/refund', name: 'admin.refund_order', methods: ['POST'])]
final class RefundOrderController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new RefundOrderCommand(
            orderId: $id,
        ));

        return new JsonResponse(['message' => 'Order refunded.']);
    }
}
