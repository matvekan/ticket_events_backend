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
        $resetUrl = sprintf('%s/reset-password?token=%s', rtrim($this->frontendUrl, '/'), $token);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($userEmail)
            ->subject('Восстановление пароля')
            ->text(sprintf("Вы запросили восстановление пароля.\n\nПерейдите по ссылке, чтобы задать новый пароль (действует 1 час):\n%s", $resetUrl))
            ->html(sprintf(
                '<p>Вы запросили восстановление пароля.</p><p>Перейдите по ссылке, чтобы задать новый пароль (ссылка действует 1 час):</p><p><a href="%s">%s</a></p>',
                htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8'),
            ));

        $this->mailer->send($email);
    }
}
