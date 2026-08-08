<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class GetOrderDetailsQuery implements QueryInterface
{
    public function __construct(
        #[Assert\Uuid]
        public readonly Uuid $orderId,

        #[Assert\Uuid]
        public readonly ?Uuid $userId = null,
    ) {
    }
}
