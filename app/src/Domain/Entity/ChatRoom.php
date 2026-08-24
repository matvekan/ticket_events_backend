<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\ChatRoomId;

class ChatRoom
{
    private string $id;
    private User $user;
    private \DateTimeImmutable $createdAt;

    /** @var ChatMessage[] */
    private $messages = [];

    private function __construct(ChatRoomId $id, User $user, \DateTimeImmutable $createdAt)
    {
        $this->id = $id->toString();
        $this->user = $user;
        $this->createdAt = $createdAt;
    }

    public static function create(User $user, ?ClockInterface $clock = null, ?IdGeneratorInterface $ids = null, ?ChatRoomId $id = null): self
    {
        $clock = $clock ?? new \App\Infrastructure\Shared\SystemClock();
        $chatId = $id ?? new ChatRoomId($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        return new self($chatId, $user, $clock->now());
    }

    public function id(): ChatRoomId
    {
        return new ChatRoomId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function user(): User
    {
        return $this->user;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}