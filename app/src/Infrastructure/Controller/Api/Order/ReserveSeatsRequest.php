<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class ReserveSeatsRequest
{
    /** @var string[] */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    #[Assert\All([new Assert\Uuid()])]
    public array $seatIds = [];
}