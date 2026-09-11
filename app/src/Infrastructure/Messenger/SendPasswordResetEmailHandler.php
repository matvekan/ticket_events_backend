<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Application\Message\SendPasswordResetEmail;
use App\Infrastructure\Mailer\PasswordResetMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendPasswordResetEmailHandler
{
    public function __construct(
        private PasswordResetMailer $mailer,
    ) {
    }

    public function __invoke(SendPasswordResetEmail $message): void
    {
        $this->mailer->sendResetLink($message->email, $message->token);
    }
}
