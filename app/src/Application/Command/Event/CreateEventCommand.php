<?php

declare(strict_types=1);

namespace App\Application\Command\Event;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateEventCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public readonly string $title,
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 5000)]
        public readonly string $description,
        #[Assert\NotNull]
        public readonly \DateTimeImmutable $date,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $venueId,
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Collection(
                fields: [
                    'seatId' => [new Assert\NotBlank(), new Assert\Uuid()],
                    'priceAmount' => [new Assert\NotNull(), new Assert\Positive()],
                ],
                allowExtraFields: false,
                allowMissingFields: false,
            ),
        ])]
        public readonly array $seats,
    ) {
    }
}
