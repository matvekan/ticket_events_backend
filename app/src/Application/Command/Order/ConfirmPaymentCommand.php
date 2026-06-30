<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

final class ConfirmPaymentCommand implements CommandInterface
{
    public function __construct(
        public readonly Uuid $orderId,
    ) {
    }
}
