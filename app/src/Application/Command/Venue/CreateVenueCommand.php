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
        #[Assert\Range(min: -90, max: 90)]
        public readonly ?float $latitude = null,
        #[Assert\Range(min: -180, max: 180)]
        public readonly ?float $longitude = null,
    ) {
    }
}
