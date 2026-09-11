<?php

declare(strict_types=1);

namespace App\Domain\Service\Order;

use App\Domain\Entity\Order;
use App\Domain\Entity\Service\OrderTicketFactoryInterface;
use App\Domain\Entity\Ticket;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\UserId;

final readonly class OrderTicketFactory implements OrderTicketFactoryInterface
{
    public function __construct(
        private TicketCodeGenerator $ticketCodeGenerator,
    ) {
    }

    public function create(
        UserId $userId,
        array $seats,
        ClockInterface $clock,
        IdGeneratorInterface $ids,
    ): Order {
        $order = Order::create($userId, $clock, $ids);

        foreach ($seats as $seat) {
            $seat->reserve();
            $ticket = Ticket::create(
                $order,
                $seat->id(),
                $seat->price(),
                $this->ticketCodeGenerator->generate(),
                $ids,
            );
            $order->addTicket($ticket);
        }

        $order->markSeatsAsReserved();

        return $order;
    }
}
