<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class GetUserOrdersQuery implements QueryInterface
{
    public function __construct(
        #[Assert\Uuid]
        public readonly Uuid $userId,
    ) {
    }
}
