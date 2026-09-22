<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\PaymentStatus;

class Payment
{
    private function __construct(
        private PaymentId $id,
        private OrderId $orderId,
        private int $amount,
        private PaymentStatus $status,
        /** @phpstan-ignore property.onlyWritten */
        private \DateTimeImmutable $createdAt,
        /** @phpstan-ignore property.onlyWritten */
        private ?\DateTimeImmutable $paidAt = null,
        /** @phpstan-ignore property.onlyWritten */
        private ?\DateTimeImmutable $failedAt = null,
    ) {
        $this->status = PaymentStatus::Pending;
    }

    public static function place(OrderId $orderId, int $amount, ClockInterface $clock, IdGeneratorInterface $ids): self
    {
        return new self(
            new PaymentId($ids->generate()),
            $orderId,
            $amount,
            PaymentStatus::Pending,
            $clock->now()
        );
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function rawId(): string
    {
        return $this->id->toString();
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function markPaid(ClockInterface $clock): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending payments can be marked as paid.');
        }

        $this->status = PaymentStatus::Paid;
        $this->paidAt = $clock->now();
    }

    public function markFailed(ClockInterface $clock): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending payments can be marked as failed.');
        }

        $this->status = PaymentStatus::Failed;
        $this->failedAt = $clock->now();
    }

    public function restart(): void
    {
        if ($this->status !== PaymentStatus::Failed) {
            throw new BusinessRuleViolationException('Only failed payments can be restarted.');
        }

        $this->status = PaymentStatus::Pending;
        $this->paidAt = null;
        $this->failedAt = null;
    }

    public function markRefunded(ClockInterface $clock): void
    {
        if ($this->status !== PaymentStatus::Paid) {
            throw new BusinessRuleViolationException('Only paid payments can be refunded.');
        }

        $this->status = PaymentStatus::Refunded;
        $this->paidAt = null;
    }
}
