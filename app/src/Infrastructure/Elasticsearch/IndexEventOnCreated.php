<?php

declare(strict_types=1);

namespace App\Infrastructure\Elasticsearch;

use App\Domain\Event\EventCreatedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class IndexEventOnCreated
{
    public function __construct(
        private readonly EventIndexer $indexer,
    ) {
    }

    public function __invoke(EventCreatedEvent $event): void
    {
        $this->indexer->indexEvent($event->getEventId());
    }
}
