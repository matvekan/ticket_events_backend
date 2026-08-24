<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ChatMessage;
use App\Domain\ValueObject\ChatRoomId;

interface ChatMessageRepositoryInterface
{
    /** @return ChatMessage[] */
    public function findByRoomId(ChatRoomId $roomId): array;

    public function save(ChatMessage $message): void;

    /**
     * Returns the latest message per room, keyed by room id (RFC 4122).
     *
     * @param \App\Domain\Entity\ChatRoom[] $rooms
     * @return array<string, ChatMessage>
     */
    public function findLatestForRooms(array $rooms): array;
}
