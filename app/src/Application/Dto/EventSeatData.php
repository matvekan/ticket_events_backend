<?php

declare(strict_types=1);

namespace App\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class EventSeatData
{
    public function __construct(
        #[Assert\Uuid]
        public string $seatId,
        #[Assert\Positive]
        public int $priceAmount,
    ) {
    }
}
