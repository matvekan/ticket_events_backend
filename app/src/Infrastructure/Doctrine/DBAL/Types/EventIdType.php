<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\EventId;

final class EventIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return EventId::class;
    }

    public function getName(): string
    {
        return 'event_id';
    }
}
