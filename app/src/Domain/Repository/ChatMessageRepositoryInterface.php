<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ChatMessage;
use Symfony\Component\Uid\Uuid;

interface ChatMessageRepositoryInterface
{
    /** @return ChatMessage[] */
    public function findByRoomId(Uuid $roomId): array;

    public function save(ChatMessage $message): void;

    /**
     * Returns the latest message per room, keyed by room id (RFC 4122).
     *
     * @param ChatRoom[] $rooms
     * @return array<string, ChatMessage>
     */
    public function findLatestForRooms(array $rooms): array;
}