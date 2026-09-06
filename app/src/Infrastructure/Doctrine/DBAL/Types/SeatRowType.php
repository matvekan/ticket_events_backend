<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\SeatRow;

final class SeatRowType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return SeatRow::class;
    }

    public function getName(): string
    {
        return 'seat_row';
    }
}
