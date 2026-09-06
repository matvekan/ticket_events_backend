<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Query\Order\GetUserOrdersQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/my', name: 'order.my', methods: ['GET'])]
#[OA\Tag(name: 'Orders')]
#[OA\Get(
    path: '/api/orders/my',
    summary: 'Get current user orders',
    tags: ['Orders'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of user orders', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class GetUserOrdersController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetUserOrdersQuery(
            $this->security->getUser()->id()->toString(),
        )));
    }
}

