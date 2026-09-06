<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use App\Domain\Event\EventCancelledEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class IndexEventOnCancelled
{
    public function __construct(
        private readonly EventIndexer $indexer,
    ) {
    }

    public function __invoke(EventCancelledEvent $event): void
    {
        $this->indexer->indexEvent($event->eventId());
    }
}