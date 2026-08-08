<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\ConfirmPaymentCommand;
use App\Application\Service\Order\PaymentService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ConfirmPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private PaymentService $paymentService,
    ) {
    }

    public function __invoke(ConfirmPaymentCommand $command): void
    {
        $this->paymentService->confirmPayment($command->orderId, $command->userId);
    }
}
