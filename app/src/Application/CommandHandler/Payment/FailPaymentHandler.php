<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Payment;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Payment\FailPaymentCommand;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\PaymentStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class FailPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(FailPaymentCommand $command): void
    {
        $this->transactionManager->transactional(function () use ($command): void {
            $payment = $this->payments->findById(new PaymentId($command->paymentId()->toRfc4122()));
            if (!$payment) {
                throw new EntityNotFoundException('Payment not found.');
            }

            if ($payment->status() !== PaymentStatus::Pending) {
                throw new BusinessRuleViolationException('Only pending payments can be declined.');
            }

            $payment->markFailed();
            $this->payments->save($payment);
        });
    }
}