<?php

declare(strict_types=1);

namespace App\Application\Service\Auth;

use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserPasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function register(string $name, string $email, string $password): void
    {
        $emailVo = new Email($email);

        if ($this->users->findByEmail($emailVo)) {
            throw new BusinessRuleViolationException('Email already registered.');
        }

        $this->transactionManager->transactional(function () use ($emailVo, $name, $password): void {
            $user = User::create(new Name($name), $emailVo);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->updatePassword($hashedPassword);

            $this->users->save($user);
        });
    }
}
