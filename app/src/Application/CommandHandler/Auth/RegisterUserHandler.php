<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = new Email($command->email);

        if ($this->userRepo->findByEmail($email)) {
            throw new \DomainException('Email already registered.');
        }

        $user = new User(new Name($command->name), $email);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
        $user->setPassword($hashedPassword);

        $this->userRepo->save($user);
    }
}
