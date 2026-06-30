<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\EventDetailsDto;
use App\Application\Dto\EventDto;
use App\Application\Dto\SeatDto;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\ValueObject\SeatStatus;

final class EventDtoFactory
{
    public function __construct(
        private readonly SeatDtoFactory $seatDtoFactory,
    ) {
    }

    /** @param Event[] $events @return EventDto[] */
    public function fromEventList(array $events): array
    {
        return array_map(fn (Event $event): EventDto => $this->fromEvent($event), $events);
    }

    public function fromEvent(Event $event): EventDto
    {
        $prices = $this->getPriceRange($event);

        return new EventDto(
            id: $event->getId()->toRfc4122(),
            title: $event->getTitle(),
            description: $event->getDescription(),
            date: $event->getDate()->format('c'),
            venueName: $event->getVenue()->getName(),
            venueCity: $event->getVenue()->getCity(),
            priceMin: $prices['min'],
            priceMax: $prices['max'],
            status: $event->getStatus()->value,
        );
    }

    public function fromEventDetails(Event $event): EventDetailsDto
    {
        $prices = $this->getPriceRange($event);

        $availableSeats = array_map(
            fn (EventSeat $eventSeat): SeatDto => $this->seatDtoFactory->fromEventSeat($eventSeat),
            $event->getEventSeats()->filter(fn (EventSeat $es): bool => $es->isAvailable())->toArray(),
        );

        return new EventDetailsDto(
            id: $event->getId()->toRfc4122(),
            title: $event->getTitle(),
            description: $event->getDescription(),
            date: $event->getDate()->format('c'),
            venueName: $event->getVenue()->getName(),
            venueAddress: $event->getVenue()->getAddress(),
            venueCity: $event->getVenue()->getCity(),
            priceMin: $prices['min'],
            priceMax: $prices['max'],
            status: $event->getStatus()->value,
            availableSeats: $availableSeats,
        );
    }

    /** @return array{min: float, max: float} */
    private function getPriceRange(Event $event): array
    {
        $prices = array_map(
            fn (EventSeat $eventSeat): float => $eventSeat->getPriceAsFloat(),
            $event->getEventSeats()->toArray(),
        );

        return [
            'min' => !empty($prices) ? min($prices) : 0.0,
            'max' => !empty($prices) ? max($prices) : 0.0,
        ];
    }
}
