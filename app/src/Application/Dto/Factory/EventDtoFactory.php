<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\EventDetailsDto;
use App\Application\Dto\EventDto;
use App\Application\Dto\PriceRangeDto;
use App\Application\Dto\SeatDto;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;

final class EventDtoFactory
{
    public function __construct(
        private readonly SeatDtoFactory $seatDtoFactory,
    ) {
    }


    public function fromEventList(array $events): array
    {
        return array_map(fn (Event $event): EventDto => $this->fromEvent($event), $events);
    }

    public function fromEvent(Event $event): EventDto
    {
        $prices = $this->getPriceRange($event);

        return new EventDto(
            id: $event->id()->toString(),
            title: (string) $event->title(),
            description: (string) $event->description(),
            date: $event->date()->format('c'),
            venueName: (string) $event->venue()->name(),
            venueCity: (string) $event->venue()->city(),
            priceMin: $prices->min,
            priceMax: $prices->max,
            priceCurrency: $prices->currency,
            status: $event->status()->value,
        );
    }

    public function fromEventDetails(Event $event): EventDetailsDto
    {
        $prices = $this->getPriceRange($event);

        $seats = array_map(
            fn (EventSeat $eventSeat): SeatDto => $this->seatDtoFactory->fromEventSeat($eventSeat),
            $event->eventSeats(),
        );

        return new EventDetailsDto(
            id: $event->id()->toString(),
            title: (string) $event->title(),
            description: (string) $event->description(),
            date: $event->date()->format('c'),
            venueName: (string) $event->venue()->name(),
            venueAddress: (string) $event->venue()->address(),
            venueCity: (string) $event->venue()->city(),
            venueLatitude: $event->venue()->latitude(),
            venueLongitude: $event->venue()->longitude(),
            priceMin: $prices->min,
            priceMax: $prices->max,
            priceCurrency: $prices->currency,
            status: $event->status()->value,
            seats: $seats,
        );
    }


    private function getPriceRange(Event $event): PriceRangeDto
    {
        $prices = array_map(
            fn (EventSeat $eventSeat): int => $eventSeat->price()->amount(),
            $event->eventSeats(),
        );

        $seats = $event->eventSeats();

        return new PriceRangeDto(
            min: $prices !== [] ? min($prices) : 0,
            max: $prices !== [] ? max($prices) : 0,
            currency: $seats !== [] ? $seats[0]->price()->currency() : 'BYN',
        );
    }
}
