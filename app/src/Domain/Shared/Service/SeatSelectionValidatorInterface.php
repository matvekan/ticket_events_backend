<?php

declare(strict_types=1);

namespace App\Domain\Shared\Service;

use App\Domain\Entity\EventSeat;
use App\Domain\Shared\ClockInterface;

interface SeatSelectionValidatorInterface
{
    /**
     * @param EventSeat[] $seats
     */
    public function validate(array $seats, ClockInterface $clock): void;
}