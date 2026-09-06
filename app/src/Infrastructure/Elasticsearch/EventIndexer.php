<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use App\Application\Dto\Factory\EventDtoFactory;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\ValueObject\EventId;
use Elastic\Elasticsearch\Client;
use Psr\Log\LoggerInterface;

final class EventIndexer
{
    private const INDEX = 'events';

    public function __construct(
        private readonly Client $elasticsearch,
        private readonly EventRepositoryInterface $events,
        private readonly EventDtoFactory $eventDtoFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function indexEvent(string|EventId $eventId): void
    {
        $eventIdVo = $eventId instanceof EventId ? $eventId : EventId::fromString($eventId);
        $rawId = $eventIdVo->toString();

        try {
            $event = $this->events->findById($eventIdVo);
            if (!$event) {
                $this->logger->warning('EventIndexer: event not found {eventId}', ['eventId' => $rawId]);

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
                    'price_currency' => $dto->priceCurrency,
                    'status' => $dto->status,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('EventIndexer failed for {eventId}: {error}', [
                'eventId' => $rawId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
