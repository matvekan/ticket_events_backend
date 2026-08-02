<?php

declare(strict_types=1);

namespace App\Application\Command\Venue;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateVenueCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 255)]
        public readonly string $address,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 100)]
        public readonly string $city,
    ) {
    }
}
