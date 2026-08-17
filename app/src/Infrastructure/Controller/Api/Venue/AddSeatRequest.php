<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Domain\ValueObject\SeatType;
use Symfony\Component\Validator\Constraints as Assert;

final class AddSeatRequest
{
    #[Assert\NotBlank]
    public string $row;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $number;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [SeatType::class, 'validTypes'])]
    public string $type;

    public ?string $sector = null;
}