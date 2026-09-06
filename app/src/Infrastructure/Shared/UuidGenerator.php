<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared;

use App\Domain\Shared\IdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class UuidGenerator implements IdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
