<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class GetOrderDetailsQuery implements QueryInterface
{
    public readonly Uuid $parsedOrderId;
    public readonly ?Uuid $parsedUserId;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $orderId,

        #[Assert\Uuid]
        public readonly ?string $userId = null,
    ) {
        $this->parsedOrderId = Uuid::fromString($orderId);
        $this->parsedUserId = $userId !== null ? Uuid::fromString($userId) : null;
    }

    public function orderId(): Uuid
    {
        return $this->parsedOrderId;
    }

    public function userId(): ?Uuid
    {
        return $this->parsedUserId;
    }
}
