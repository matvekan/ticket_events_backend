<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\Factory\OrderDtoFactory;
use App\Application\Dto\OrderDto;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly OrderDtoFactory $orderDtoFactory,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $query): ?OrderDto
    {
        $order = $this->orderRepo->findById($query->orderId);
        if (!$order) {
            return null;
        }

        return $this->orderDtoFactory->fromOrder($order);
    }
}
