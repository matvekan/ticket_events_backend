<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\ChatMessageId;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\MessageText;
use App\Domain\ValueObject\UserId;

class ChatMessage
{
    public const MAX_TEXT_LENGTH = MessageText::MAX_LENGTH;

    private function __construct(
        private ChatMessageId $id,
        private ChatRoomId $roomId,
        private UserId $senderId,
        private MessageText $text,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(
        ChatRoomId $roomId,
        UserId $senderId,
        string $text,
        ClockInterface $clock,
        IdGeneratorInterface $ids,
    ): self {
        return new self(
            new ChatMessageId($ids->generate()),
            $roomId,
            $senderId,
            new MessageText($text),
            $clock->now(),
        );
    }

    public function id(): ChatMessageId
    {
        return $this->id;
    }

    public function rawId(): string
    {
        return $this->id->toString();
    }

    public function roomId(): ChatRoomId
    {
        return $this->roomId;
    }

    public function senderId(): UserId
    {
        return $this->senderId;
    }

    public function text(): MessageText
    {
        return $this->text;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
