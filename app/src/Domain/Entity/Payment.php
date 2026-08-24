<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\PaymentStatus;

class Payment
{
    private string $id;
    private Order $order;
    private int $amount;
    private PaymentStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $paidAt = null;
    private ?\DateTimeImmutable $failedAt = null;

    private function __construct(PaymentId $id, Order $order, int $amount, ClockInterface $clock)
    {
        $this->id = $id->toString();
        $this->order = $order;
        $this->amount = $amount;
        $this->status = PaymentStatus::Pending;
        $this->createdAt = $clock->now();
    }

    public static function create(Order $order, int $amount, ?ClockInterface $clock = null, ?IdGeneratorInterface $ids = null, ?PaymentId $id = null): self
    {
        $clock = $clock ?? new \App\Infrastructure\Shared\SystemClock();
        $paymentId = $id ?? new PaymentId($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        return new self($paymentId, $order, $amount, $clock);
    }

    public function id(): PaymentId
    {
        return new PaymentId($this->id);
    }

    public function rawId(): string { return $this->id; }

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

    public function markPaid(?ClockInterface $clock = null): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending payments can be marked as paid.');
        }

        $this->status = PaymentStatus::Paid;
        $this->paidAt = $clock ? $clock->now() : new \DateTimeImmutable();
    }

    public function markFailed(?ClockInterface $clock = null): void
    {
        if ($this->status !== PaymentStatus::Pending) {
            throw new BusinessRuleViolationException('Only pending payments can be marked as failed.');
        }

        $this->status = PaymentStatus::Failed;
        $this->failedAt = $clock ? $clock->now() : new \DateTimeImmutable();
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
