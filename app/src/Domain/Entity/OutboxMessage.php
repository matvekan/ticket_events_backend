<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;

final class OutboxMessage
{
    private string $id;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $sentAt = null;
    private int $attempts = 0;

    public function __construct(private readonly string $messageClass, private readonly string $body, ClockInterface $clock, IdGeneratorInterface $ids)
    {
        $this->id = $ids->generate();
        $this->createdAt = $clock->now();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function messageClass(): string
    {
        return $this->messageClass;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markFailed(): void
    {
        ++$this->attempts;
    }

    public function markSent(\DateTimeImmutable $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function isSent(): bool
    {
        return $this->sentAt !== null;
    }
}
