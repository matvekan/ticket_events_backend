<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\PaymentDto;

/**
 * Read-side port for payments.
 */
interface PaymentReadRepositoryInterface
{
    /**
     * When $userId is provided the payment is returned only if the
     * underlying order belongs to that user (IDOR protection).
     */
    public function findByOrderId(string $orderId, ?string $userId): ?PaymentDto;
}
