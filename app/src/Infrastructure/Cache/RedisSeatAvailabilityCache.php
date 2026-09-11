<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Shared\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;

final class RedisSeatAvailabilityCache implements CacheInterface
{
    private const PREFIX = 'seat.available.';

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function get(string $key): mixed
    {
        $item = $this->cache->getItem(self::PREFIX . strtolower(trim($key)));

        if (! $item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_array($value) ? $value : null;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $item = $this->cache->getItem(self::PREFIX . strtolower(trim($key)));
        $item->set($value);
        $item->expiresAfter($ttl);
        $this->cache->save($item);
    }

    public function delete(string $key): void
    {
        $this->cache->deleteItem(self::PREFIX . strtolower(trim($key)));
    }

    public function has(string $key): bool
    {
        $item = $this->cache->getItem(self::PREFIX . strtolower(trim($key)));

        return $item->isHit();
    }
}
