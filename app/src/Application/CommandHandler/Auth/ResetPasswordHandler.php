<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\ResetPasswordCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Service\PasswordHasherInterface;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ResetPasswordHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ResetPasswordCommand $command): void
    {
        $user = $this->users->findByPasswordResetTokenHash(hash('sha256', $command->token));

        if ($user === null || !$user->isPasswordResetTokenValid($this->clock->now())) {
            throw new DomainException('Invalid or expired reset token.');
        }

        $this->transactionManager->transactional(function () use ($user, $command): void {
            $user->changePassword($this->passwordHasher->hash($user, $command->password));
            $user->clearPasswordResetToken();
            $this->users->save($user);
        });
    }
}
