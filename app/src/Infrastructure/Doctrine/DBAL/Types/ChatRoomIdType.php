<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\ChatRoomId;

final class ChatRoomIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return ChatRoomId::class;
    }

    public function getName(): string
    {
        return 'chat_room_id';
    }
}
