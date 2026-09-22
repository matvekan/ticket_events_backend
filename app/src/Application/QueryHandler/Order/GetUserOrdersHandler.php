<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Query\Order\GetUserOrdersQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserOrdersHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    /**
     * @return array<int, \App\Application\Dto\OrderDto>
     */
    public function __invoke(GetUserOrdersQuery $query): array
    {
        return $this->orders->findOrderByUserId($query->userId);
    }
}
