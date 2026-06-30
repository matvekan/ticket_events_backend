<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\OrderDto;
use App\Application\Dto\TicketDto;
use App\Domain\Entity\Order;
use App\Domain\Entity\Ticket;

final class OrderDtoFactory
{
    public function __construct(
        private readonly TicketDtoFactory $ticketDtoFactory,
    ) {
    }

    public function fromOrder(Order $order): OrderDto
    {
        return new OrderDto(
            id: $order->getId()->toRfc4122(),
            status: $order->getStatus()->value,
            total: $order->getTotalAsFloat(),
            createdAt: $order->getCreatedAt()->format('c'),
            tickets: $this->ticketDtoFactory->fromTicketList($order->getTickets()->toArray()),
        );
    }

    /** @param Order[] $orders @return OrderDto[] */
    public function fromOrderList(array $orders): array
    {
        return array_map(fn (Order $order): OrderDto => $this->fromOrder($order), $orders);
    }
}
