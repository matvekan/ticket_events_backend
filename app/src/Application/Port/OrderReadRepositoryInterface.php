<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\OrderDto;

/**
 * Read-side port for orders. Returns projection DTOs only —
 * never write-model aggregates.
 */
interface OrderReadRepositoryInterface
{
    public function findByIdAndUser(string $orderId, ?string $userId): ?OrderDto;

    /** @return OrderDto[] */
    public function findByUserId(string $userId): array;
}
