<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\Uuid;

final class UuidDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Uuid
    {
        return Uuid::fromRfc4122($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Uuid::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Uuid::class => true];
    }
}
