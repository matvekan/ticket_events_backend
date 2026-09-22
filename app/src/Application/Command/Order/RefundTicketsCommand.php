<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RefundTicketsCommand implements CommandInterface
{
    /**
     * @param array<int, string> $ticketIds
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $orderId,
        #[Assert\Uuid]
        #[Assert\NotBlank]
        public ?string $userId,
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Uuid()])]
        public array $ticketIds,
    ) {
    }
}
