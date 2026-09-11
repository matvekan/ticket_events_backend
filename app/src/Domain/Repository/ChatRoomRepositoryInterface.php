<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ChatRoom;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;

interface ChatRoomRepositoryInterface
{
    public function findById(ChatRoomId $id): ?ChatRoom;

    public function findByUserId(UserId $userId): ?ChatRoom;

    public function findAll(): array;

    public function findSupportRooms(): array;

    public function save(ChatRoom $room): void;
}
