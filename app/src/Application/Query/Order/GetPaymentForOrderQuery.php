<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GetPaymentForOrderQuery implements QueryInterface
{
    public function __construct(
        #[Assert\Uuid]
        public readonly string $orderId,
    ) {
    }
}