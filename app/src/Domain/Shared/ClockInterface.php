<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
