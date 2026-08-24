<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Order;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\UserId;

interface OrderRepositoryInterface
{
    public function findById(OrderId $id): ?Order;

    /** @return Order[] */
    public function findByUserId(UserId $userId): array;

    /** @return Order[] */
    public function findPendingExpired(\DateTimeImmutable $cutoff): array;

    public function save(Order $order): void;
}
