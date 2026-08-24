<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\Factory\OrderDtoFactory;
use App\Application\Dto\OrderDto;
use App\Application\Query\Order\GetOrderDetailsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetOrderDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderDtoFactory $orderDtoFactory,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $query): ?OrderDto
    {
        $order = $this->orders->findById(new OrderId($query->orderId()->toRfc4122()));
        if (!$order) {
            return null;
        }

        if ($query->userId() !== null && !$order->userId()->equals(new UserId($query->userId()->toRfc4122()))) {
            return null;
        }

        return $this->orderDtoFactory->fromOrder($order);
    }
}
