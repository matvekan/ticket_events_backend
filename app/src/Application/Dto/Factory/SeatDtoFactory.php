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
            id: $seat->id()->toRfc4122(),
            row: (string) $seat->row(),
            number: $seat->number()->toValue(),
            sector: $seat->sector() !== null ? (string) $seat->sector() : null,
            type: $seat->type()->value,
        );
    }

    /** @param Seat[] $seats @return SeatDto[] */
    public function fromSeatList(array $seats): array
    {
        return array_map(fn (Seat $seat): SeatDto => $this->fromSeat($seat), $seats);
    }

    public function fromEventSeat(EventSeat $eventSeat): SeatDto
    {
        $seat = $eventSeat->seat();
        return new SeatDto(
            id: $eventSeat->id()->toRfc4122(),
            row: (string) $seat->row(),
            number: $seat->number()->toValue(),
            sector: $seat->sector() !== null ? (string) $seat->sector() : null,
            type: $seat->type()->value,
            priceAmount: $eventSeat->price()->amount(),
            priceCurrency: $eventSeat->price()->currency(),
            status: $eventSeat->status()->value,
        );
    }

    /** @param EventSeat[] $eventSeats @return SeatDto[] */
    public function fromAvailableSeats(array $eventSeats): array
    {
        return array_map(fn (EventSeat $eventSeat): SeatDto => $this->fromEventSeat($eventSeat), $eventSeats);
    }
}
