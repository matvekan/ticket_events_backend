<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Domain\Event\OrderPaidEvent;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Mailer\OrderMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class SendPaymentConfirmation
{
    public function __construct(
        private readonly OrderMailer $mailer,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function __invoke(OrderPaidEvent $event): void
    {
        $user = $this->users->findById(new UserId($event->userId()));
        if (!$user) {
            return;
        }

        $this->mailer->sendPaymentConfirmation(
            (string) $user->email(),
            (string) $event->orderId(),
        );
    }
}
