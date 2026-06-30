<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Dto\OrderDto;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/orders/{id}', name: 'order.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
final class GetOrderController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $order = $this->queryBus->dispatch(new GetOrderDetailsQuery(Uuid::fromRfc4122($id)));

        if (!$order instanceof OrderDto) {
            return new JsonResponse(['error' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($order);
    }
}
