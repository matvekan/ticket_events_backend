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

    private string $id;
    private string $roomId;
    private string $senderId;
    private string $text;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        ChatMessageId $id,
        ChatRoomId $roomId,
        UserId $senderId,
        MessageText $text,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->toString();
        $this->roomId = $roomId->toString();
        $this->senderId = $senderId->toString();
        $this->text = $text->toString();
        $this->createdAt = $createdAt;
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
        return new ChatMessageId($this->id);
    }

    public function rawId(): string { return $this->id; }

    /**
     * Reference to the ChatRoom aggregate by ID (cross-aggregate boundary).
     */
    public function roomId(): ChatRoomId
    {
        return new ChatRoomId($this->roomId);
    }

    /**
     * Reference to the User aggregate by ID (cross-aggregate boundary).
     */
    public function senderId(): UserId
    {
        return new UserId($this->senderId);
    }

    public function text(): string
    {
        return $this->text;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
