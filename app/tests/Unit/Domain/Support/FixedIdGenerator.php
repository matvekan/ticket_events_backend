<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Support;

use App\Domain\Shared\IdGeneratorInterface;

final class FixedIdGenerator implements IdGeneratorInterface
{
    /** @var string[] */
    private array $ids;
    private int $position = 0;

    /** @param string[] $ids */
    public function __construct(array $ids = [])
    {
        $this->ids = $ids !== [] ? array_values($ids) : [self::uuid(1)];
    }

    public function generate(): string
    {
        $id = $this->ids[$this->position] ?? self::uuid($this->position + 1);
        ++$this->position;

        return $id;
    }

    public static function uuid(int $seed): string
    {
        // Deterministic valid UUID v4-like string for tests.
        return sprintf('11111111-1111-4111-8111-%012d', $seed);
    }
}
