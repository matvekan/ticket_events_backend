<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/orders/{id}/refund', name: 'admin.refund_order', methods: ['POST'])]
#[OA\Tag(name: 'Admin')]
#[OA\Parameter(name: 'id', description: 'Order UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Post(
    path: '/api/admin/orders/{id}/refund',
    summary: 'Refund an order (admin)',
    tags: ['Admin'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Order refunded'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Order not found'),
        new OA\Response(response: 422, description: 'Order cannot be refunded'),
    ]
)]
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
