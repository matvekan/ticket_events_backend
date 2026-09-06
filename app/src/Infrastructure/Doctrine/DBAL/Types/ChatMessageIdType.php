<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\ChatMessageId;

final class ChatMessageIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return ChatMessageId::class;
    }

    public function getName(): string
    {
        return 'chat_message_id';
    }
}
