<?php

declare(strict_types=1);

namespace App\Application\Command\Auth;

use App\Application\Command\CommandInterface;

final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
