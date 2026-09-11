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

    public function findByUserId(UserId $userId): array;

    public function findPendingExpired(\DateTimeImmutable $cutoff): array;

    public function findByEventId(EventId $eventId): array;

    public function findByIdAndUser(string $orderId, ?string $userId): ?OrderDto;

    public function findOrderByUserId(string $userId): array;

    public function save(Order $order): void;
}
