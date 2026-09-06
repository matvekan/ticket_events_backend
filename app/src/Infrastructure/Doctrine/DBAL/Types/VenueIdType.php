<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\VenueId;

final class VenueIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return VenueId::class;
    }

    public function getName(): string
    {
        return 'venue_id';
    }
}
