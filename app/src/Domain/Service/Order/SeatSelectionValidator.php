<?php

declare(strict_types=1);

namespace App\Domain\Service\Order;

use App\Domain\Entity\Service\SeatSelectionValidatorInterface;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;

final readonly class SeatSelectionValidator implements SeatSelectionValidatorInterface
{
    /**
     * @param array<int, \App\Domain\Entity\EventSeat> $seats
     */
        public function validate(array $seats, ClockInterface $clock): void
    {
        if ($seats === []) {
            throw new BusinessRuleViolationException('No seats selected.');
        }

        $firstEvent = $seats[0]->event();
        $firstEventId = $firstEvent->id()->toString();

        $firstEvent->ensureCanBeBooked($clock);

        $seenIds = [];
        foreach ($seats as $seat) {
            $seatId = $seat->id()->toString();

            if (isset($seenIds[$seatId])) {
                throw new BusinessRuleViolationException('Duplicate seat ids are not allowed.');
            }
            $seenIds[$seatId] = true;

            if ($seat->event()->id()->toString() !== $firstEventId) {
                throw new BusinessRuleViolationException('All seats must belong to the same event.');
            }

            if (!$seat->isAvailable()) {
                throw new BusinessRuleViolationException('Some seats are not available.');
            }
        }
    }
}
