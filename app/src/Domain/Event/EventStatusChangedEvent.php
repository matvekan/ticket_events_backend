<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class EventStatusChangedEvent
{
    public function __construct(
        private readonly string $eventId,
        private readonly string $title,
        private readonly string $newStatus,
    ) {
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function newStatus(): string
    {
        return $this->newStatus;
    }
}
