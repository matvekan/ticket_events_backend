<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Venue;

use App\Application\Dto\Factory\VenueDtoFactory;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Venue\ListVenuesQuery;
use App\Domain\Repository\VenueRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListVenuesHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly VenueRepositoryInterface $venues,
        private readonly VenueDtoFactory $venueDtoFactory,
    ) {
    }

    public function __invoke(ListVenuesQuery $query): array
    {
        return $this->venueDtoFactory->fromVenueList($this->venues->findAll());
    }
}
