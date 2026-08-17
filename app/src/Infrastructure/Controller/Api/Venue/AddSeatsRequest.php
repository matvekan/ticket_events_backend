<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use Symfony\Component\Validator\Constraints as Assert;

final class AddSeatsRequest
{
    /** @var AddSeatRequest[] */
    #[Assert\Valid]
    #[Assert\Count(min: 1)]
    public array $seats = [];
}