<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class GetPaymentForOrderQuery implements QueryInterface
{
    public readonly Uuid $parsedOrderId;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $orderId,
    ) {
        $this->parsedOrderId = Uuid::fromString($orderId);
    }

    public function orderId(): Uuid
    {
        return $this->parsedOrderId;
    }
}