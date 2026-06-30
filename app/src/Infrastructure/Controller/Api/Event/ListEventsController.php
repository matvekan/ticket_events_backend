<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Event;

use App\Application\Query\Event\ListEventsQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'event.list', methods: ['GET'])]
final class ListEventsController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(#[MapQueryString] ListEventsQuery $query): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch($query));
    }
}
