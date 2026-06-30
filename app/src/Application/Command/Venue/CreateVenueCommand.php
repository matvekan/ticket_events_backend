<?php

declare(strict_types=1);

namespace App\Application\Command\Venue;

use App\Application\Command\CommandInterface;

final class CreateVenueCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $address,
        public readonly string $city,
    ) {
    }
}
