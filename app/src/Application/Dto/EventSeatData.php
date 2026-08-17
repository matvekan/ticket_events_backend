<?php

declare(strict_types=1);

namespace App\Application\Dto;

use Symfony\Component\Uid\Uuid;

readonly class EventSeatData
{
    public function __construct(
        public Uuid $seatId,
        public int $priceAmount,
    ) {
    }
}
