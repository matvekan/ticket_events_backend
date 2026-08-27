<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;

class ChatRoom
{
    private string $id;
    private string $userId;
    private \DateTimeImmutable $createdAt;

    private function __construct(ChatRoomId $id, UserId $userId, \DateTimeImmutable $createdAt)
    {
        $this->id = $id->toString();
        $this->userId = $userId->toString();
        $this->createdAt = $createdAt;
    }

    public static function create(UserId $userId, ClockInterface $clock, IdGeneratorInterface $ids): self
    {
        return new self(new ChatRoomId($ids->generate()), $userId, $clock->now());
    }

    public function id(): ChatRoomId
    {
        return new ChatRoomId($this->id);
    }

    public function rawId(): string
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return new UserId($this->userId);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
