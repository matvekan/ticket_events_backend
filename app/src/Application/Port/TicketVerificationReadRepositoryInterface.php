<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\TicketVerificationData;

/**
 * Read-side port for ticket verification lookups.
 */
interface TicketVerificationReadRepositoryInterface
{
    public function findByCode(string $code): ?TicketVerificationData;
}
