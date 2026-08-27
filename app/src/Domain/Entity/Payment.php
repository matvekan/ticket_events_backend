<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\PaymentStatus;
use App\Domain\ValueObject\OrderId;

class Payment
{
    private string $id;
    private string $orderId;
    private int $amount;
    private PaymentStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $paidAt = null;
    private ?\DateTimeImmutable $failedAt = null;

    private function __construct(PaymentId $id, OrderId $orderId, int $amount, ClockInterface $clock)
    {
        $this->id = $id->toString();
        $this->orderId = $orderId->toString();
        $this->amount = $amount;
        $this->status = PaymentStatus::Pending;
        $this->createdAt = $clock->now();
    }

    public static function place(OrderId $orderId, int $amount, ClockInterface $clock, IdGeneratorInterface $ids): self
    {
        return new self(new PaymentId($ids->generate()), $orderId, $amount, $clock);
    }

    public function id(): PaymentId
    {
        return new PaymentId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function orderId(): OrderId
    {
        return new OrderId($this->orderId);
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
}
