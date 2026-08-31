<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;

class ChatRoom
{
    private function __construct(
        private readonly ChatRoomId $id,
        private readonly UserId $userId,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(UserId $userId, ClockInterface $clock, IdGeneratorInterface $ids): self
    {
        return new self(new ChatRoomId($ids->generate()), $userId, $clock->now());
    }

    public function id(): ChatRoomId
    {
        return $this->id;
    }

    public function rawId(): string
    {
        return $this->id->toString();
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
