<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\ChatMessageId;
use App\Domain\ValueObject\MessageText;

class ChatMessage
{
    public const MAX_TEXT_LENGTH = MessageText::MAX_LENGTH;

    private string $id;
    private ChatRoom $room;
    private User $sender;
    private string $text;
    private \DateTimeImmutable $createdAt;

    private function __construct(ChatMessageId $id, ChatRoom $room, User $sender, MessageText $text, \DateTimeImmutable $createdAt)
    {
        $this->id = $id->toString();
        $this->room = $room;
        $this->sender = $sender;
        $this->text = $text->toString();
        $this->createdAt = $createdAt;
    }

    public static function create(ChatRoom $room, User $sender, string $text, ?ClockInterface $clock = null, ?IdGeneratorInterface $ids = null, ?ChatMessageId $id = null): self
    {
        $clock = $clock ?? new \App\Infrastructure\Shared\SystemClock();
        $msgId = $id ?? new ChatMessageId($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        $messageText = new MessageText($text);
        return new self($msgId, $room, $sender, $messageText, $clock->now());
    }

    public function id(): ChatMessageId
    {
        return new ChatMessageId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function room(): ChatRoom
    {
        return $this->room;
    }

    public function sender(): User
    {
        return $this->sender;
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