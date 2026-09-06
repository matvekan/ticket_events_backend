<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    
    public function findByIds(array $ids): array;

    public function findByEmail(Email $email): ?User;

    public function findByPasswordResetTokenHash(string $tokenHash): ?User;

    public function save(User $user): void;
}
