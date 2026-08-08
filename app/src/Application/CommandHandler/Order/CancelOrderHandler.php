<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Service\Order\OrderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CancelOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $this->orderService->cancel($command->orderId, $command->userId);
    }
}
