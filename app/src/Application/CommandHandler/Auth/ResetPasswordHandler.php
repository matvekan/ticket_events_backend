<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\ResetPasswordCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final readonly class ResetPasswordHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserPasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function __invoke(ResetPasswordCommand $command): void
    {
        $user = $this->users->findByPasswordResetTokenHash(hash('sha256', $command->token));

        if ($user === null || !$user->isPasswordResetTokenValid(new \DateTimeImmutable())) {
            throw new DomainException('Invalid or expired reset token.');
        }

        $this->transactionManager->transactional(function () use ($user, $command): void {
            $user->updatePassword($this->passwordHasher->hashPassword($user, $command->password));
            $user->clearPasswordResetToken();
            $this->users->save($user);
        });
    }
}
