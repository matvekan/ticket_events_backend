<?php

declare(strict_types=1);

namespace App\Application\Cache;

use App\Application\Dto\SeatDto;
use Symfony\Component\Uid\Uuid;

interface SeatAvailabilityCacheInterface
{
    /** @return SeatDto[]|null */
    public function getAvailable(Uuid $eventId): ?array;

    /** @param SeatDto[] $seats */
    public function setAvailable(Uuid $eventId, array $seats): void;

    public function invalidate(Uuid $eventId): void;
}
