<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\OrderDto;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $query): ?OrderDto
    {

        return $this->orders->findByIdAndUser($query->orderId, $query->userId);
    }
}
