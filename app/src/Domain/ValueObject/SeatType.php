<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum SeatType: string
{
    case Standard = 'standard';
    case VIP = 'vip';
    case Premium = 'premium';

    /**
     * @return array<int, string>
     */
    public static function validTypes(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
