<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\EventSeatId;

final class EventSeatIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return EventSeatId::class;
    }

    public function getName(): string
    {
        return 'event_seat_id';
    }
}
