<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\VenueName;

final class VenueNameType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return VenueName::class;
    }

    public function getName(): string
    {
        return 'venue_name';
    }
}
