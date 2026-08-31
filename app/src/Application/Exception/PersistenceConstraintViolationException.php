<?php

declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Transient persistence failure caused by a storage-level constraint
 * (e.g. unique index). Implementations of repository ports translate
 * vendor-specific exceptions into this one so that the Application layer
 * never depends on Doctrine/DBAL types.
 */
final class PersistenceConstraintViolationException extends \RuntimeException
{
}
