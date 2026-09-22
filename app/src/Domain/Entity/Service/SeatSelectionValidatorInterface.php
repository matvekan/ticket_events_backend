<?php

declare(strict_types=1);

namespace App\Domain\Entity\Service;

use App\Domain\Shared\ClockInterface;

interface SeatSelectionValidatorInterface
{
    /**
     * @param array<int, \App\Domain\Entity\EventSeat> $seats
     */
        public function validate(array $seats, ClockInterface $clock): void;
}
