<?php

declare(strict_types=1);

namespace App\Application\Dto;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

readonly class EventSeatData
{
    public readonly Uuid $parsedSeatId;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $seatId,
        #[Assert\Positive]
        public int $priceAmount,
    ) {
        $this->parsedSeatId = Uuid::fromString($seatId);
    }

    public function seatId(): Uuid
    {
        return $this->parsedSeatId;
    }
}
