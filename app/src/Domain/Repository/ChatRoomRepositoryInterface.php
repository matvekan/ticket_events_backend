<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ChatRoom;
use Symfony\Component\Uid\Uuid;

interface ChatRoomRepositoryInterface
{
    public function findById(Uuid $id): ?ChatRoom;

    public function findByUserId(Uuid $userId): ?ChatRoom;

    /** @return ChatRoom[] */
    public function findAll(): array;

    public function save(ChatRoom $room): void;
}