<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Dto\OrderDto;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/{id}', name: 'order.get', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
final class GetOrderController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $order = $this->queryBus->dispatch(new GetOrderDetailsQuery(
            orderId: $id,
            userId: $this->security->getUser()?->id()?->toRfc4122(),
        ));

        if (!$order instanceof OrderDto) {
            return new JsonResponse(['error' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($order);
    }
}
