<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Service\Order\PaymentService;
use App\Domain\Entity\Payment;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class StartPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private PaymentService $paymentService,
    ) {
    }

    public function __invoke(StartPaymentCommand $command): Payment
    {
        return $this->paymentService->startPayment(
            Uuid::fromString($command->orderId),
            $command->userId !== null ? Uuid::fromString($command->userId) : null,
        );
    }
}