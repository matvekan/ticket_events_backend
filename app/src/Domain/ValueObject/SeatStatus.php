<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum SeatStatus: string
{
    case Free = 'free';
    case Reserved = 'reserved';
    case Sold = 'sold';
}
