<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Infrastructure\Controller\Api\Shared\RequiresDomainUserTrait;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/{id}/cancel', name: 'order.cancel', methods: ['POST'])]
#[OA\Tag(name: 'Orders')]
#[OA\Parameter(name: 'id', description: 'Order UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Post(
    path: '/api/orders/{id}/cancel',
    summary: 'Cancel an order',
    tags: ['Orders'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Order cancelled'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Order not found'),
        new OA\Response(response: 422, description: 'Order cannot be cancelled'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class CancelOrderController
{
    use RequiresDomainUserTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $userId = $this->getDomainUser($this->security)->id()->toString();
        $this->commandBus->dispatch(new CancelOrderCommand(
            orderId: $id,
            userId: $userId,
        ));

        return new JsonResponse(['message' => 'Order cancelled.']);
    }
}
