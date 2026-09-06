<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;
use App\Infrastructure\Security\DomainUserAdapter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mock-bank', name: 'mock_bank.')]
final class MockBankController
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    #[Route('/{paymentId}/charge', name: 'charge', methods: ['POST'])]
    public function charge(string $paymentId): JsonResponse
    {
        $payment = $this->payments->findById(new PaymentId($paymentId));
        if ($payment === null) {
            return new JsonResponse(['message' => 'Payment not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $payment->orderId()->toString(),
            userId: $this->actorUserId(),
        ));

        return new JsonResponse(['status' => 'success', 'orderId' => $payment->orderId()->toString()]);
    }

    private function actorUserId(): ?string
    {
        $user = $this->security->getUser();
        return $user instanceof DomainUserAdapter ? $user->id()->toString() : null;
    }
}
