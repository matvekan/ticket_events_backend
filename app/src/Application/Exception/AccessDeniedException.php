<?php

declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Thrown when the caller is not allowed to perform the operation.
 * Transport-agnostic: mapped to HTTP 403 by the API exception listener.
 */
final class AccessDeniedException extends \RuntimeException
{
}
