<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Admin;

use App\Application\Dto\TicketVerificationDto;
use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\OrderStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetTicketVerificationHandler implements QueryHandlerInterface
{
    private const TICKET_CODE_PATTERN = '/^TKT-[A-Z0-9]{8}$/';

    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {
    }

    public function __invoke(GetTicketVerificationQuery $query): TicketVerificationDto
    {
        $code = strtoupper(trim($query->code));

        if (!preg_match(self::TICKET_CODE_PATTERN, $code)) {
            return new TicketVerificationDto(false, 'Билет с таким кодом не найден.');
        }

        $ticket = $this->tickets->findByCode($code);
        if ($ticket === null) {
            return new TicketVerificationDto(false, 'Билет с таким кодом не найден.');
        }

        $order = $ticket->order();
        $event = $ticket->eventSeat()->event();

        if ($order->status() !== OrderStatus::Paid) {
            return new TicketVerificationDto(false, 'Заказ по этому билету не оплачен.');
        }

        if ($event->status() === EventStatus::Cancelled) {
            return new TicketVerificationDto(false, 'Событие отменено.');
        }

        $seat = $ticket->eventSeat()->seat();

        return new TicketVerificationDto(true, null, [
            'code' => (string) $ticket->code(),
            'eventTitle' => (string) $event->title(),
            'eventDate' => $event->date()->format('Y-m-d H:i:s'),
            'venueName' => (string) $event->venue()->name(),
            'seat' => sprintf('%s%s', $seat->row(), $seat->number()->toValue()),
            'orderStatus' => $order->status()->value,
        ]);
    }
}