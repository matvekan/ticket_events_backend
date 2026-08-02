<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\OrderDto;
use App\Domain\Entity\Order;

final class OrderDtoFactory
{
    public function __construct(
        private readonly TicketDtoFactory $ticketDtoFactory,
    ) {
    }

    public function fromOrder(Order $order): OrderDto
    {
        return new OrderDto(
            id: $order->id()->toRfc4122(),
            status: $order->status()->value,
            total: $order->totalPrice()->asFloat(),
            createdAt: $order->createdAt()->format('c'),
            tickets: $this->ticketDtoFactory->fromTicketList($order->tickets()->toArray()),
        );
    }

    /** @param Order[] $orders @return OrderDto[] */
    public function fromOrderList(array $orders): array
    {
        return array_map(fn (Order $order): OrderDto => $this->fromOrder($order), $orders);
    }
}
