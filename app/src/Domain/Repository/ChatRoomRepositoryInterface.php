<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Application\Dto\ChatRoomDto;
use App\Domain\Entity\ChatRoom;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;

interface ChatRoomRepositoryInterface
{
    public function findById(ChatRoomId $id): ?ChatRoom;

    public function findByUserId(UserId $userId): ?ChatRoom;

    /**
     * @return array<int, ChatRoom>
     */
    public function findAll(): array;

    /**
     * @return array<int, ChatRoomDto>
     */
    public function findSupportRooms(): array;

    public function save(ChatRoom $room): void;
}
