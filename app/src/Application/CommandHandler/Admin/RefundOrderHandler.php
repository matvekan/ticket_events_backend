<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Admin;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\OrderId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RefundOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepositoryInterface $orders,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RefundOrderCommand $command): void
    {
        $orderIdVo = new OrderId($command->orderId()->toRfc4122());
        $this->transactionManager->transactional(function () use ($orderIdVo): array {
            $order = $this->orders->findById($orderIdVo);
            if (!$order) {
                throw new EntityNotFoundException('Order not found.');
            }

            $order->refund($this->clock);

            foreach ($order->tickets() as $ticket) {
                $ticket->refund();
                $ticket->eventSeat()->unsell();
            }

            $this->orders->save($order);

            return $order->releaseEvents();
        });
    }
}