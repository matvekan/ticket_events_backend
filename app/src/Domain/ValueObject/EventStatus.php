<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case SoldOut = 'sold_out';
    case Cancelled = 'cancelled';
}
