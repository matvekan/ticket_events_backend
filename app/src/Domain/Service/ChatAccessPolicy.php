<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use App\Domain\ValueObject\Role;

/**
 * Pure domain policy: who may participate in a support chat room.
 * Spans two aggregates (ChatRoom + User), hence a domain service.
 */
final class ChatAccessPolicy
{
    public function canParticipate(ChatRoom $room, User $user): bool
    {
        return $room->userId()->equals($user->id())
            || $this->isSupport($user);
    }

    public function isSupport(User $user): bool
    {
        return in_array(Role::Admin->value, $user->roles(), true);
    }
}
