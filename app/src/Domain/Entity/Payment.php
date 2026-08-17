<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\PaymentStatus;
use Symfony\Component\Uid\Uuid;

class Payment
{
    private Uuid $id;
    private Order $order;
    private int $amount;
    private PaymentStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $paidAt = null;
    private ?\DateTimeImmutable $failedAt = null;

    private function __construct(Order $order, int $amount)
    {
        $this->id = Uuid::v7();
        $this->order = $order;
        $this->amount = $amount;
        $this->status = PaymentStatus::Pending;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(Order $order, int $amount): self
    {
        return new self($order, $amount);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function failedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function markPaid(): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending payments can be marked as paid.');
        }

        $this->status = PaymentStatus::Paid;
        $this->paidAt = new \DateTimeImmutable();
    }

    public function markFailed(): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending payments can be marked as failed.');
        }

        $this->status = PaymentStatus::Failed;
        $this->failedAt = new \DateTimeImmutable();
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
