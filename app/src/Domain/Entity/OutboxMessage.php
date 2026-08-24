<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class OutboxMessage
{
    private string $id;
    private string $messageClass;
    private string $body;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $sentAt = null;
    private int $attempts = 0;

    public function __construct(string $messageClass, string $body, ?\App\Domain\Shared\ClockInterface $clock = null, ?\App\Domain\Shared\IdGeneratorInterface $ids = null, ?string $id = null)
    {
        $this->id = $id ?? ($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        $this->messageClass = $messageClass;
        $this->body = $body;
        $this->createdAt = $clock ? $clock->now() : new \DateTimeImmutable();
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

    public function markFailed(): void
    {
        ++$this->attempts;
    }

    public function markSent(): void
    {
        $this->sentAt = new \DateTimeImmutable();
    }
}