<?php

declare(strict_types=1);

namespace App\Application\Command\Event;

use App\Application\Command\CommandInterface;
use App\Application\Dto\EventSeatData;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateEventCommand implements CommandInterface
{
    /** @param EventSeatData[] $seats */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public readonly string $title,

        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 5000)]
        public readonly string $description,

        #[Assert\NotNull]
        public readonly \DateTimeImmutable $date,

        #[Assert\Uuid]
        public readonly string $venueId,

        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Type(EventSeatData::class)])]
        public readonly array $seats,
    ) {
    }
}
