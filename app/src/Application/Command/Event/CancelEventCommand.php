<?php

declare(strict_types=1);

namespace App\Application\Command\Event;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

final class CancelEventCommand implements CommandInterface
{
    public function __construct(
        public readonly Uuid $eventId,
    ) {
    }
}
