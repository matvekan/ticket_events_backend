<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class PasswordResetMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
    ) {
    }

    public function sendResetLink(string $userEmail, string $token): void
    {
        $resetUrl = \sprintf('%s/reset-password?token=%s', rtrim($this->frontendUrl, '/'), $token);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($userEmail)
            ->subject('Password Reset')
            ->text(\sprintf("You requested a password reset.\n\nFollow the link to set a new password (valid for 1 hour):\n%s", $resetUrl))
            ->html(\sprintf(
                '<p>You requested a password reset.</p><p>Follow the link to set a new password (link valid for 1 hour):</p><p><a href="%s">%s</a></p>',
                htmlspecialchars($resetUrl, \ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($resetUrl, \ENT_QUOTES, 'UTF-8'),
            ));

        $this->mailer->send($email);
    }
}
