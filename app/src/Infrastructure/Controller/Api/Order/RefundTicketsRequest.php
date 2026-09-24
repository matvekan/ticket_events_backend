<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class RefundTicketsRequest
{
    /**
     * @param array<int, string> $ticketIds
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\NotBlank(),
            new Assert\Type('string'),
            new Assert\Uuid()
        ])]
        public readonly array $ticketIds,
    ) {
    }
}
