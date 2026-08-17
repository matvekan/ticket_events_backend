<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Symfony\Component\Uid\Uuid;

class ChatMessage
{
    public const MAX_TEXT_LENGTH = 2000;

    private Uuid $id;
    private ChatRoom $room;
    private User $sender;
    private string $text;
    private \DateTimeImmutable $createdAt;

    private function __construct(ChatRoom $room, User $sender, string $text)
    {
        $this->id = Uuid::v7();
        $this->room = $room;
        $this->sender = $sender;
        $this->text = $text;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(ChatRoom $room, User $sender, string $text): self
    {
        return new self($room, $sender, $text);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

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