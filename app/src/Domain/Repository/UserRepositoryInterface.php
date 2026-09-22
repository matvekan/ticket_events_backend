<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    /**
     * @param array<int, \App\Domain\ValueObject\UserId> $ids
     * @return array<string, \App\Domain\Entity\User>
     */
    public function findByIds(array $ids): array;

    public function findByEmail(Email $email): ?User;

    public function findByPasswordResetTokenHash(string $tokenHash): ?User;

    public function save(User $user): void;
}
