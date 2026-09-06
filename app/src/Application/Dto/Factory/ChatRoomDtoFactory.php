<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\ChatRoomDto;
use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;

final class ChatRoomDtoFactory
{
    public function fromRoom(ChatRoom $room, ?User $owner): ChatRoomDto
    {
        return new ChatRoomDto(
            id: $room->id()->toString(),
            userId: $room->userId()->toString(),
            userEmail: $owner !== null ? (string) $owner->email() : 'Unknown',
            createdAt: $room->createdAt()->format('c'),
            lastMessage: null,
        );
    }
}
