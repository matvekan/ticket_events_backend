<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum SeatType: string
{
    case Standard = 'standard';
    case VIP = 'vip';
    case Premium = 'premium';
}
