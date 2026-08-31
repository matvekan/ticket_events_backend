<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Auth;

use App\Application\Command\Auth\ForgotPasswordCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Message\SendPasswordResetEmail;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class ForgotPasswordHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private MessageBusInterface $messageBus,
        private TransactionManagerInterface $transactionManager,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(ForgotPasswordCommand $command): void
    {
        $user = $this->users->findByEmail(new Email($command->email));

        if ($user === null) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        $this->transactionManager->transactional(function () use ($user, $token): void {
            $user->setPasswordResetToken(
                hash('sha256', $token),
                $this->clock->now()->modify('+1 hour'),
            );
            $this->users->save($user);
        });

        $this->messageBus->dispatch(new SendPasswordResetEmail($user->email()->toValue(), $token));
    }
}
