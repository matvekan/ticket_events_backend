<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Admin;

use App\Application\Dto\TicketVerificationDto;
use App\Application\Port\TicketVerificationReadRepositoryInterface;
use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\ValueObject\EventStatus;
use App\Domain\ValueObject\OrderStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetTicketVerificationHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly TicketVerificationReadRepositoryInterface $tickets,
    ) {
    }

    public function __invoke(GetTicketVerificationQuery $query): TicketVerificationDto
    {
        $data = $this->tickets->findByCode($query->code);

        if ($data === null) {
            return new TicketVerificationDto(false, 'Билет с таким кодом не найден.');
        }

        if ($data->orderStatus !== OrderStatus::Paid->value) {
            return new TicketVerificationDto(false, 'Заказ по этому билету не оплачен.');
        }

        if ($data->eventStatus === EventStatus::Cancelled->value) {
            return new TicketVerificationDto(false, 'Событие отменено.');
        }

        return new TicketVerificationDto(true, null, [
            'code' => $data->code,
            'eventTitle' => $data->eventTitle,
            'eventDate' => $data->eventDate,
            'venueName' => $data->venueName,
            'seat' => sprintf('%s%s', $data->seatRow, $data->seatNumber),
            'orderStatus' => $data->orderStatus,
        ]);
    }
}
