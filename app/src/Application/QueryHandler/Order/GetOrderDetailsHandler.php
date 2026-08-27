<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\OrderDto;
use App\Application\Port\OrderReadRepositoryInterface;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderReadRepositoryInterface $orders,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $query): ?OrderDto
    {
        // Ownership enforced in the projection: foreign orders read as "not found".
        return $this->orders->findByIdAndUser($query->orderId, $query->userId);
    }
}
