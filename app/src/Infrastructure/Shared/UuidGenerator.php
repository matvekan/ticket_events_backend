<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared;

use Symfony\Component\Uid\Uuid;

final class UuidGenerator
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
