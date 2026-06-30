<?php

declare(strict_types=1);

namespace App\Application\Dto;

final class VenueDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $address,
        public readonly string $city,
    ) {
    }
}
