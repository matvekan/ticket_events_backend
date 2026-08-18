<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final readonly class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserPasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = new Email($command->email);

        if ($this->users->findByEmail($email)) {
            throw new BusinessRuleViolationException('Email already registered.');
        }

        $this->transactionManager->transactional(function () use ($email, $command): void {
            $user = User::create(new Name($command->name), $email);
            $user->updatePassword($this->passwordHasher->hashPassword($user, $command->password));

            $this->users->save($user);
        });
    }
}