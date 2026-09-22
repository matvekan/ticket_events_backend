<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
    ) {
    }

    public function sendPaymentConfirmation(string $userEmail, string $orderId): void
    {
        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($userEmail)
            ->subject('Payment Confirmed')
            ->text(\sprintf('Your order %s has been paid successfully.', $orderId));

        $this->mailer->send($email);
    }
}
