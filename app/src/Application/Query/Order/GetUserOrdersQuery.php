<?php

declare(strict_types=1);

namespace App\Application\Query\Order;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class GetUserOrdersQuery implements QueryInterface
{
    public readonly Uuid $parsedUserId;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId,
    ) {
        $this->parsedUserId = Uuid::fromString($userId);
    }

    public function userId(): Uuid
    {
        return $this->parsedUserId;
    }
}
