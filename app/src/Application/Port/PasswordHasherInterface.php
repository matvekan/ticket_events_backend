<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Entity\User;

/**
 * Hashes plain-text passwords for domain users.
 * Implementation lives in Infrastructure (bridges to Symfony security).
 */
interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;
}
