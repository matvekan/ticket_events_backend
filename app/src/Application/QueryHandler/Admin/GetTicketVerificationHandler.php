<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Admin;

use App\Application\Dto\TicketVerificationDto;
use App\Application\Exception\EventCancelledException;
use App\Application\Exception\OrderNotPaidException;
use App\Application\Exception\TicketNotFoundException;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\OrderStatus;
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
            throw new TicketNotFoundException($query->code);
        }

        if ($ticket->orderStatus !== OrderStatus::Paid->value) {
            throw new OrderNotPaidException($query->code);
        }

        if ($ticket->eventStatus === EventStatus::Cancelled->value) {
            throw new EventCancelledException($query->code);
        }

        return new TicketVerificationDto(true, null, [
            'code' => $ticket->code,
            'eventTitle' => $ticket->eventTitle,
            'eventDate' => $ticket->eventDate,
            'venueName' => $ticket->venueName,
            'seat' => sprintf('%s%s', $ticket->seatRow, $ticket->seatNumber),
            'orderStatus' => $ticket->orderStatus,
        ]);
    }
}
