<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\SeatId;

final class SeatIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return SeatId::class;
    }

    public function getName(): string
    {
        return 'seat_id';
    }
}
