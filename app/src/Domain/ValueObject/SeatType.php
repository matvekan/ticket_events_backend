<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum SeatType: string
{
    case Standard = 'standard';
    case VIP = 'vip';
    case Premium = 'premium';

    public static function validTypes(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
