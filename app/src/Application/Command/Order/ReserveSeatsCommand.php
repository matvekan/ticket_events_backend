<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ReserveSeatsCommand implements CommandInterface
{
    /**
     * @param array<int, string> $eventSeatIds
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId,
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Uuid()])]
        public readonly array $eventSeatIds,
    ) {
    }
}
