<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Application\Dto\OrderDto;
use App\Domain\Entity\Order;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\UserId;

interface OrderRepositoryInterface
{
    public function findById(OrderId $id): ?Order;

    /**
     * @return array<int, \App\Domain\Entity\Order>
     */
    public function findByUserId(UserId $userId): array;

    /**
     * @return array<int, Order>
     */
    public function findPendingExpired(\DateTimeImmutable $cutoff): array;

    /**
     * @return array<int, Order>
     */
    public function findByEventId(EventId $eventId): array;

    public function findByIdAndUser(string $orderId, ?string $userId): ?OrderDto;

    /**
     * @return array<int, \App\Application\Dto\OrderDto>
     */
    public function findOrderByUserId(string $userId): array;

    public function save(Order $order): void;
}
