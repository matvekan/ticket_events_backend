<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Message\ExpirePendingOrders;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ExpirePendingOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private MessageBusInterface $commandBus,
        private ClockInterface $clock,
        private int $pendingOrderTtlMinutes,
    ) {
    }

    public function __invoke(ExpirePendingOrders $message): void
    {
        $cutoff = $this->clock->now()->modify(sprintf('-%d minutes', $this->pendingOrderTtlMinutes));

        foreach ($this->orders->findPendingExpired($cutoff) as $order) {
            try {
                $this->commandBus->dispatch(new CancelOrderCommand(
                    orderId: $order->id()->toRfc4122(),
                ));
            } catch (DomainException) {
                continue;
            }
        }
    }
}
