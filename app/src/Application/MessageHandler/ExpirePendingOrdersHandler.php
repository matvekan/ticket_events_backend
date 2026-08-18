<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Message\ExpirePendingOrders;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ExpirePendingOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(ExpirePendingOrders $message): void
    {
        $cutoff = (new \DateTimeImmutable())->modify('-15 minutes');

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