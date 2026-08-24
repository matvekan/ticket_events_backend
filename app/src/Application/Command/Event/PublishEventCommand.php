<?php

declare(strict_types=1);

namespace App\Application\Command\Event;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class PublishEventCommand implements CommandInterface
{
    public readonly Uuid $parsedEventId;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $eventId,
    ) {
        $this->parsedEventId = Uuid::fromString($eventId);
    }

    public function eventId(): Uuid
    {
        return $this->parsedEventId;
    }
}
