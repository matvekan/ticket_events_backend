<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;

final class GetEventDetailsQuery implements QueryInterface
{
    public function __construct(
        public readonly Uuid $eventId,
    ) {
    }
}
