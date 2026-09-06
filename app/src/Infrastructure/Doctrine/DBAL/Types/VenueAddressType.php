<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\VenueAddress;

final class VenueAddressType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return VenueAddress::class;
    }

    public function getName(): string
    {
        return 'venue_address';
    }
}
