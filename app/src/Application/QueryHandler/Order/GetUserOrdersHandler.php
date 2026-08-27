<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\OrderDto;
use App\Application\Port\OrderReadRepositoryInterface;
use App\Application\Query\Order\GetUserOrdersQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserOrdersHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderReadRepositoryInterface $orders,
    ) {
    }

    /** @return OrderDto[] */
    public function __invoke(GetUserOrdersQuery $query): array
    {
        return $this->orders->findByUserId($query->userId);
    }
}
