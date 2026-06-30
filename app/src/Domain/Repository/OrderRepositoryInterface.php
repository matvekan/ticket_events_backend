<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Order;
use Symfony\Component\Uid\Uuid;

interface OrderRepositoryInterface
{
    public function findById(Uuid $id): ?Order;

    /** @return Order[] */
    public function findByUserId(Uuid $userId): array;

    public function save(Order $order): void;
}
