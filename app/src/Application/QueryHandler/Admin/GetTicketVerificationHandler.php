<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Admin;

use App\Application\Dto\TicketVerificationDto;
use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\TicketStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetTicketVerificationHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {
    }

    public function __invoke(GetTicketVerificationQuery $query): TicketVerificationDto
    {
        $ticket = $this->tickets->findVerificationByCode($query->code);

        if ($ticket === null) {
            return new TicketVerificationDto(false, \sprintf('Ticket "%s" not found.', $query->code));
        }

        if ($ticket->ticketStatus === TicketStatus::Refunded->value) {
            return new TicketVerificationDto(false, \sprintf('Ticket "%s" has been refunded.', $query->code));
        }

        if ($ticket->ticketStatus === TicketStatus::Cancelled->value) {
            return new TicketVerificationDto(false, \sprintf('Ticket "%s" has been cancelled.', $query->code));
        }

        if ($ticket->orderStatus !== OrderStatus::Paid->value) {
            return new TicketVerificationDto(false, \sprintf('Order for ticket "%s" is not paid.', $query->code));
        }

        if ($ticket->eventStatus === EventStatus::Cancelled->value) {
            return new TicketVerificationDto(false, \sprintf('Event for ticket "%s" is cancelled.', $query->code));
        }

        return new TicketVerificationDto(true, null, [
            'code' => $ticket->code,
            'eventTitle' => $ticket->eventTitle,
            'eventDate' => $ticket->eventDate,
            'venueName' => $ticket->venueName,
            'seat' => \sprintf('%s%s', $ticket->seatRow, $ticket->seatNumber),
            'orderStatus' => $ticket->orderStatus,
        ]);
    }
}
