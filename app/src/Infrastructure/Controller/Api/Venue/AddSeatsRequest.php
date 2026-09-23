<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Domain\ValueObject\SeatType;
use Symfony\Component\Validator\Constraints as Assert;

final class AddSeatsRequest
{
    /** @var array<int, array{row: string, number: int|string, type: string, sector?: string|null}> */
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Collection(
            fields: [
                'row' => [new Assert\NotBlank()],
                'number' => [new Assert\NotNull(), new Assert\Positive()],
                'type' => [new Assert\NotBlank(), new Assert\Choice(callback: [SeatType::class, 'validTypes'])],
                'sector' => new Assert\Optional([new Assert\Type('string')]),
            ],
            allowExtraFields: false,
            allowMissingFields: false,
        ),
    ])]
    public array $seats = [];
}
