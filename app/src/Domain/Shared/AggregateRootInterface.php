<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface AggregateRootInterface
{
    /**
     * @return object[]
     */
    public function releaseEvents(): array;
}
