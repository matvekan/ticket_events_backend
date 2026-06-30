<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use App\Domain\Event\OrderPaidEvent;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class SendPaymentConfirmation
{
    public function __construct(
        private readonly OrderMailer $mailer,
        private readonly UserRepositoryInterface $userRepo,
    ) {
    }

    public function __invoke(OrderPaidEvent $event): void
    {
        $user = $this->userRepo->findById($event->getUserId());
        if (!$user) {
            return;
        }

        $this->mailer->sendPaymentConfirmation(
            (string) $user->getEmail(),
            (string) $event->getOrderId(),
        );
    }
}
