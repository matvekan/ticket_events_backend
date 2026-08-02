<?php

declare(strict_types=1);

namespace App\Application\Command\Auth;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public readonly string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 6)]
        public readonly string $password,
    ) {
    }
}
