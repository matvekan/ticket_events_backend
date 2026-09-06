<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\SeatNumber;

final class SeatNumberType extends AbstractIntegerValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return SeatNumber::class;
    }

    public function getName(): string
    {
        return 'seat_number';
    }
}
