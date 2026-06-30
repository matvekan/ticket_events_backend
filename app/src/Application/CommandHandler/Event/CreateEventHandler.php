<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Event;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Event\CreateEventCommand;
use App\Application\Event\EventBusInterface;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Event\EventCreatedEvent;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateEventHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly VenueRepositoryInterface $venueRepo,
        private readonly SeatRepositoryInterface $seatRepo,
        private readonly EventRepositoryInterface $eventRepo,
        private readonly EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateEventCommand $command): void
    {
        $venue = $this->venueRepo->findById($command->venueId);
        if (!$venue) {
            throw new \DomainException('Venue not found.');
        }

        $event = new Event($command->title, $command->description, $command->date, $venue);

        foreach ($command->seats as $seatData) {
            $seat = $this->seatRepo->findById($seatData['seatId']);
            if (!$seat) {
                throw new \DomainException('Seat not found.');
            }
            $eventSeat = new EventSeat($event, $seat, $seatData['priceAmount']);
            $event->addEventSeat($eventSeat);
        }

        $this->eventRepo->save($event);
        $this->eventBus->dispatch(new EventCreatedEvent($event->getId(), $event->getTitle()));
    }
}
