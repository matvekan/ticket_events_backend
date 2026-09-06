<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\EventTitle;

final class EventTitleType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return EventTitle::class;
    }

    public function getName(): string
    {
        return 'event_title';
    }
}
