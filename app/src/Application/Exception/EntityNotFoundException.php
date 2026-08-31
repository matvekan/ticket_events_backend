<?php

declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Application-level "not found" so handlers do not have to pick
 * between domain and transport exceptions. Mapped to HTTP 404.
 */
final class EntityNotFoundException extends \RuntimeException
{
}
