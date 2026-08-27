<?php

declare(strict_types=1);

namespace App\Application\Cache;

use App\Application\Dto\SeatDto;

/**
 * Transport-agnostic cache port: identifiers are plain strings so that
 * neither Application nor Infrastructure depend on a specific UUID type.
 */
interface SeatAvailabilityCacheInterface
{
    /** @return SeatDto[]|null */
    public function getAvailable(string $eventId): ?array;

    /** @param SeatDto[] $seats */
    public function setAvailable(string $eventId, array $seats): void;

    public function invalidate(string $eventId): void;
}
