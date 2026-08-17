<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Payment;
use Symfony\Component\Uid\Uuid;

interface PaymentRepositoryInterface
{
    public function findById(Uuid $id): ?Payment;

    public function findByOrderId(Uuid $orderId): ?Payment;

    public function save(Payment $payment): void;
}
