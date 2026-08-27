<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Dto\SeatDto;
use Psr\Cache\CacheItemPoolInterface;

final class RedisSeatAvailabilityCache implements SeatAvailabilityCacheInterface
{
    private const PREFIX = 'seat.available.';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly int $ttlSeconds,
    ) {
    }

    /** @return SeatDto[]|null */
    public function getAvailable(string $eventId): ?array
    {
        $item = $this->cache->getItem(self::key($eventId));

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_array($value) ? $value : null;
    }

    /** @param SeatDto[] $seats */
    public function setAvailable(string $eventId, array $seats): void
    {
        $item = $this->cache->getItem(self::key($eventId));
        $item->set($seats);
        $item->expiresAfter($this->ttlSeconds);
        $this->cache->save($item);
    }

    public function invalidate(string $eventId): void
    {
        $this->cache->deleteItem(self::key($eventId));
    }

    private static function key(string $eventId): string
    {
        return self::PREFIX . strtolower(trim($eventId));
    }
}
