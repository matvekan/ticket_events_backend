<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\ChatRoomDto;
use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

final class ChatRoomDtoFactory
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function fromRoom(ChatRoom $room): ChatRoomDto
    {
        $owner = $this->users->findById($room->userId());

        return new ChatRoomDto(
            id: $room->id()->toRfc4122(),
            userId: $room->userId()->toRfc4122(),
            userEmail: $owner !== null ? (string) $owner->email() : 'Unknown',
            createdAt: $room->createdAt()->format('c'),
            lastMessage: null,
        );
    }
}
