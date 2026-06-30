<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use App\Application\Dto\Factory\EventDtoFactory;
use App\Domain\Repository\EventRepositoryInterface;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Uid\Uuid;

final class EventIndexer
{
    private const INDEX = 'events';

    public function __construct(
        private readonly Client $elasticsearch,
        private readonly EventRepositoryInterface $eventRepo,
        private readonly EventDtoFactory $eventDtoFactory,
    ) {
    }

    public function indexEvent(Uuid $eventId): void
    {
        $event = $this->eventRepo->findById($eventId);
        if (!$event) {
            return;
        }

        $dto = $this->eventDtoFactory->fromEvent($event);

        $this->elasticsearch->index([
            'index' => self::INDEX,
            'id' => $dto->id,
            'body' => [
                'title' => $dto->title,
                'description' => $dto->description,
                'date' => $dto->date,
                'venue_name' => $dto->venueName,
                'venue_city' => $dto->venueCity,
                'price_min' => $dto->priceMin,
                'price_max' => $dto->priceMax,
                'status' => $dto->status,
            ],
        ]);
    }
}
