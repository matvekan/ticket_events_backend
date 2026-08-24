<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\Factory\OrderDtoFactory;
use App\Application\Dto\OrderDto;
use App\Application\Query\Order\GetUserOrdersQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserOrdersHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderDtoFactory $orderDtoFactory,
    ) {
    }

    /** @return OrderDto[] */
    public function __invoke(GetUserOrdersQuery $query): array
    {
        $orders = $this->orders->findByUserId(new UserId($query->userId()->toRfc4122()));

        return $this->orderDtoFactory->fromOrderList($orders);
    }
}
