<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\EventDescription;

final class EventDescriptionType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return EventDescription::class;
    }

    public function getName(): string
    {
        return 'event_description';
    }
}
