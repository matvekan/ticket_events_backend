<?php

declare(strict_types=1);

namespace App\Application\Command\Admin;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

final class RefundOrderCommand implements CommandInterface
{
    public function __construct(
        public readonly Uuid $orderId,
        public readonly Uuid $adminId,
    ) {
    }
}
