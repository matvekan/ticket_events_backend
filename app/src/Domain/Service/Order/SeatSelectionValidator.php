<?php

declare(strict_types=1);

namespace App\Domain\Service\Order;

use App\Domain\Entity\EventSeat;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\Service\SeatSelectionValidatorInterface;
use App\Domain\ValueObject\EventStatus;

final readonly class SeatSelectionValidator implements SeatSelectionValidatorInterface
{
    public function validate(array $seats, ClockInterface $clock): void
    {
        if ($seats === []) {
            throw new BusinessRuleViolationException('No seats selected.');
        }

        $ids = array_map(fn(EventSeat $s) => $s->id()->toString(), $seats);
        if (count(array_unique($ids)) !== count($ids)) {
            throw new BusinessRuleViolationException('Duplicate seat ids are not allowed.');
        }

        $firstEvent = $seats[0]->event();
        $firstEventId = $firstEvent->id()->toString();
        foreach ($seats as $seat) {
            if ($seat->event()->id()->toString() !== $firstEventId) {
                throw new BusinessRuleViolationException('All seats must belong to the same event.');
            }
        }

        if ($firstEvent->status() !== EventStatus::Published) {
            throw new BusinessRuleViolationException('Event is not published.');
        }

        if ($firstEvent->date() <= $clock->now()) {
            throw new BusinessRuleViolationException('Event has already occurred or is in the past.');
        }

        foreach ($seats as $seat) {
            if (!$seat->isAvailable()) {
                throw new BusinessRuleViolationException('Some seats are not available.');
            }
        }
    }
}