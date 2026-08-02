<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class CancelOrderCommand implements CommandInterface
{
    public function __construct(
        #[Assert\Uuid]
        public readonly Uuid $orderId,
    ) {
    }
}
