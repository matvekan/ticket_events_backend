<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class VenueDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $address,
        public string $city,
    ) {
    }
}
