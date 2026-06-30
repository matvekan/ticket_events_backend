<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Query\Order\GetUserOrdersQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/orders/my', name: 'order.my', methods: ['GET'])]
final class GetUserOrdersController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetUserOrdersQuery($this->security->getUser()->getId())));
    }
}

