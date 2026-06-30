<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;

final class GetOrderDetailsQuery implements QueryInterface
{
    public function __construct(
        public readonly Uuid $orderId,
    ) {
    }
}
