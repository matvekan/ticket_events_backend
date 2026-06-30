<?php

declare(strict_types=1);

namespace App\Domain\Event;

use Symfony\Component\Uid\Uuid;

final class EventCreatedEvent
{
    public function __construct(
        private readonly Uuid $eventId,
        private readonly string $title,
    ) {
    }

    public function getEventId(): Uuid
    {
        return $this->eventId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
