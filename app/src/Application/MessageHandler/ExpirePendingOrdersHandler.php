<?php

declare(strict_types=1);

namespace App\Application\MessageHandler;

use App\Application\Message\ExpirePendingOrders;
use App\Application\Service\Order\OrderService;
use App\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ExpirePendingOrdersHandler
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private OrderService $orderService,
    ) {
    }

    public function __invoke(ExpirePendingOrders $message): void
    {
        $cutoff = (new \DateTimeImmutable())->modify('-15 minutes');

        foreach ($this->orders->findPendingExpired($cutoff) as $order) {
            try {
                $this->orderService->cancel($order->id());
            } catch (\DomainException) {
                continue;
            }
        }
    }
}
