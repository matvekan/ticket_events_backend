<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Event;

use App\Application\Dto\Factory\EventDtoFactory;
use App\Application\Query\Event\ListAllEventsQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class ListAllEventsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly EventDtoFactory $eventDtoFactory,
    ) {
    }


    public function __invoke(ListAllEventsQuery $query): array
    {
        return $this->eventDtoFactory->fromEventList($this->events->findAll());
    }
}
