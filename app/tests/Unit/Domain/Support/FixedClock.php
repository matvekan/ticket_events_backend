<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Support;

use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;

final class FixedClock implements ClockInterface
{
    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}
