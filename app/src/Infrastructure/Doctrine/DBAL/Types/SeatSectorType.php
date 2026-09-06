<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\SeatSector;

final class SeatSectorType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return SeatSector::class;
    }

    public function getName(): string
    {
        return 'seat_sector';
    }
}
