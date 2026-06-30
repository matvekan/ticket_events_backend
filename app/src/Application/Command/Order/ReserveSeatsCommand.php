<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

final class ReserveSeatsCommand implements CommandInterface
{
    /** @param Uuid[] $eventSeatIds */
    public function __construct(
        public readonly Uuid $userId,
        public readonly array $eventSeatIds,
    ) {
    }
}
