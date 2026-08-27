<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class EventCreatedEvent
{
    public function __construct(
        private readonly string $eventId,
        private readonly string $title,
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
}
