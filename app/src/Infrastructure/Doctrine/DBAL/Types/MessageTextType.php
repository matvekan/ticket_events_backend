<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\MessageText;

final class MessageTextType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return MessageText::class;
    }

    public function getName(): string
    {
        return 'message_text';
    }
}
