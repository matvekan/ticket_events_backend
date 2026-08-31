<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Exception\PersistenceConstraintViolationException;
use App\Application\Port\PasswordHasherInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private IdGeneratorInterface $idGenerator,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = new Email($command->email);

        try {
            $this->transactionManager->transactional(function () use ($email, $command): void {
                if ($this->users->findByEmail($email) !== null) {
                    throw new BusinessRuleViolationException('Email already registered.');
                }

                $user = User::create(new UserId($this->idGenerator->generate()), new Name($command->name), $email);
                $user->changePassword($this->passwordHasher->hash($user, $command->password));

                $this->users->save($user);
            });
        } catch (PersistenceConstraintViolationException) {
            throw new BusinessRuleViolationException('Email already registered.');
        }
    }
}
