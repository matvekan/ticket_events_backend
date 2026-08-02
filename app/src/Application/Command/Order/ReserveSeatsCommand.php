<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class ReserveSeatsCommand implements CommandInterface
{
    /** @param Uuid[] $eventSeatIds */
    public function __construct(
        #[Assert\NotNull]
        public readonly Uuid $userId,

        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        public readonly array $eventSeatIds,
    ) {
    }
}
