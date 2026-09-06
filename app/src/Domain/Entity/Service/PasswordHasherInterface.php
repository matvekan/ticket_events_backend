<?php

declare(strict_types=1);

namespace App\Domain\Entity\Service;

use App\Domain\Entity\User;

interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;
}
