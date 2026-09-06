<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\VenueCity;

final class VenueCityType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return VenueCity::class;
    }

    public function getName(): string
    {
        return 'venue_city';
    }
}
