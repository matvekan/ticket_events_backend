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
            id: $ticket->id()->toRfc4122(),
            code: (string) $ticket->code(),
            eventSeatId: $ticket->eventSeat()->id()->toRfc4122(),
            eventTitle: (string) $ticket->eventSeat()->event()->title(),
            eventDate: $ticket->eventSeat()->event()->date()->format('c'),
            venueName: (string) $ticket->eventSeat()->event()->venue()->name(),
            priceAmount: $ticket->price()->amount(),
            priceCurrency: $ticket->price()->currency(),
        );
    }
}
