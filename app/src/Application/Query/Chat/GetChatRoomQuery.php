<?php

declare(strict_types=1);

namespace App\Application\Query\Chat;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GetChatRoomQuery implements QueryInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId,
    ) {
    }
}
