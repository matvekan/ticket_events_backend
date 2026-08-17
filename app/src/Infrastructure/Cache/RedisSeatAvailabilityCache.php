<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Dto\SeatDto;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Uid\Uuid;

final class RedisSeatAvailabilityCache implements SeatAvailabilityCacheInterface
{
    private const PREFIX = 'seat.available.';
    private const TTL = 30;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /** @return SeatDto[]|null */
    public function getAvailable(Uuid $eventId): ?array
    {
        $item = $this->cache->getItem(self::key($eventId));

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_array($value) ? $value : null;
    }

    /** @param SeatDto[] $seats */
    public function setAvailable(Uuid $eventId, array $seats): void
    {
        $item = $this->cache->getItem(self::key($eventId));
        $item->set($seats);
        $item->expiresAfter(self::TTL);
        $this->cache->save($item);
    }

    public function invalidate(Uuid $eventId): void
    {
        $this->cache->deleteItem(self::key($eventId));
    }

    private static function key(Uuid $eventId): string
    {
        return self::PREFIX . $eventId->toRfc4122();
    }
}