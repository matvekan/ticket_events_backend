<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Service\Order\OrderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    public function __invoke(RefundOrderCommand $command): void
    {
        $this->orderService->refund($command->orderId);
    }
}
