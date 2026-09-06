<?php

declare(strict_types=1);

namespace App\Domain\Entity\Service;

use App\Domain\Shared\ClockInterface;

interface SeatSelectionValidatorInterface
{

    public function validate(array $seats, ClockInterface $clock): void;
}
