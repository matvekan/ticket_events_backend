<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Dto\OrderDto;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryBusInterface;
use App\Infrastructure\Security\DomainUserAdapter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/orders/{id}', name: 'order.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
#[OA\Tag(name: 'Orders')]
#[OA\Parameter(name: 'id', description: 'Order UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Get(
    path: '/api/orders/{id}',
    summary: 'Get order details',
    tags: ['Orders'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Order details', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Order not found'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class GetOrderController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        $userId = null;
        if ($user instanceof DomainUserAdapter) {
            $userId = $user->id()->toString();
        } elseif ($user !== null) {
            throw new AccessDeniedException('Access denied.');
        }
        $order = $this->queryBus->dispatch(new GetOrderDetailsQuery(
            orderId: $id,
            userId: $userId,
        ));

        if (!$order instanceof OrderDto) {
            return new JsonResponse(['error' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($order);
    }
}
