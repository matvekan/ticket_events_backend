<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\SeatDto;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Seat;

final class SeatDtoFactory
{
    public function fromSeat(Seat $seat): SeatDto
    {
        return new SeatDto(
            id: $seat->getId()->toRfc4122(),
            row: $seat->getRow(),
            number: $seat->getNumber(),
            sector: $seat->getSector(),
            type: $seat->getType()->value,
        );
    }

    /** @param Seat[] $seats @return SeatDto[] */
    public function fromSeatList(array $seats): array
    {
        return array_map(fn (Seat $seat): SeatDto => $this->fromSeat($seat), $seats);
    }

    public function fromEventSeat(EventSeat $eventSeat): SeatDto
    {
        return new SeatDto(
            id: $eventSeat->getId()->toRfc4122(),
            row: $eventSeat->getSeat()->getRow(),
            number: $eventSeat->getSeat()->getNumber(),
            sector: $eventSeat->getSeat()->getSector(),
            type: $eventSeat->getSeat()->getType()->value,
            priceAmount: $eventSeat->getPriceAmount(),
            status: $eventSeat->getStatus()->value,
        );
    }

    /** @param EventSeat[] $eventSeats @return SeatDto[] */
    public function fromAvailableSeats(array $eventSeats): array
    {
        return array_map(fn (EventSeat $es): SeatDto => $this->fromEventSeat($es), $eventSeats);
    }
}
