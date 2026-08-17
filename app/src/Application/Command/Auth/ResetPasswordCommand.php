<?php

declare(strict_types=1);

namespace App\Application\Command\Auth;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $token,

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public readonly string $password,
    ) {
    }
}
