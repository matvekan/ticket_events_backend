<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\TicketDto;
use App\Domain\Entity\Ticket;

final class TicketDtoFactory
{
    /** @param Ticket[] $tickets @return TicketDto[] */
    public function fromTicketList(array $tickets): array
    {
        return array_map(fn (Ticket $ticket): TicketDto => $this->fromTicket($ticket), $tickets);
    }

    public function fromTicket(Ticket $ticket): TicketDto
    {
        return new TicketDto(
            id: $ticket->getId()->toRfc4122(),
            code: $ticket->getCode(),
            eventSeatId: $ticket->getEventSeat()->getId()->toRfc4122(),
            eventTitle: $ticket->getEventSeat()->getEvent()->getTitle(),
            eventDate: $ticket->getEventSeat()->getEvent()->getDate()->format('c'),
            venueName: $ticket->getEventSeat()->getEvent()->getVenue()->getName(),
            priceAmount: $ticket->getPriceAmount(),
        );
    }
}
