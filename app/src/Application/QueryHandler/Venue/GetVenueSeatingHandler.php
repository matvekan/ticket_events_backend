<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Venue;

use App\Application\Dto\Factory\SeatDtoFactory;
use App\Application\Dto\SeatDto;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Venue\GetVenueSeatingQuery;
use App\Domain\Repository\SeatRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetVenueSeatingHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly SeatRepositoryInterface $seatRepo,
        private readonly SeatDtoFactory $seatDtoFactory,
    ) {
    }

    /** @return SeatDto[] */
    public function __invoke(GetVenueSeatingQuery $query): array
    {
        $seats = $this->seatRepo->findByVenueId($query->venueId);

        return $this->seatDtoFactory->fromSeatList($seats);
    }
}
