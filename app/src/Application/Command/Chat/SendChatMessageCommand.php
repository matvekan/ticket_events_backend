<?php

declare(strict_types=1);

namespace App\Application\Command\Chat;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SendChatMessageCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $roomId,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $senderId,
        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public readonly string $text,
    ) {
    }
}
