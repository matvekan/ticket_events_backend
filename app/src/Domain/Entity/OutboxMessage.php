<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Symfony\Component\Uid\Uuid;

final class OutboxMessage
{
    private Uuid $id;
    private string $messageClass;
    private string $body;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $sentAt = null;
    private int $attempts = 0;

    public function __construct(string $messageClass, string $body)
    {
        $this->id = Uuid::v7();
        $this->messageClass = $messageClass;
        $this->body = $body;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): Uuid
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

    public function sentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function markFailed(): void
    {
        ++$this->attempts;
    }

    public function markSent(): void
    {
        $this->sentAt = new \DateTimeImmutable();
    }
}