<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Payment;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;

interface PaymentRepositoryInterface
{
    public function findById(PaymentId $id): ?Payment;

    public function findByOrderId(OrderId $orderId): ?Payment;

    public function save(Payment $payment): void;
}
