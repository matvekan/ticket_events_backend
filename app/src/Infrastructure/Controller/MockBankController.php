<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\FailPaymentCommand;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mock-bank', name: 'mock_bank.')]
final class MockBankController
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly CommandBusInterface $commandBus,
        private readonly string $frontendUrl,
    ) {
    }

    #[Route('/{paymentId}', name: 'checkout', methods: ['GET'])]
    public function checkout(string $paymentId): Response
    {
        $payment = $this->payments->findById(new PaymentId($paymentId));
        if ($payment === null) {
            return new JsonResponse(['message' => 'Payment not found.'], Response::HTTP_NOT_FOUND);
        }

        $order = $payment->order();

        return new JsonResponse([
            'paymentId' => $payment->id()->toRfc4122(),
            'orderId' => $order->id()->toRfc4122(),
            'amount' => $payment->amount(),
            'amountFormatted' => number_format($payment->amount() / 100, 2, '.', ' ') . ' BYN',
            'status' => $payment->status()->value,
            'actions' => [
                'charge' => sprintf('/mock-bank/%s/charge', $paymentId),
                'decline' => sprintf('/mock-bank/%s/decline', $paymentId),
            ],
            'hint' => 'Demo mode: any card 4242 4242 4242 4242 will succeed.',
        ]);
    }

    #[Route('/{paymentId}/charge', name: 'charge', methods: ['POST'])]
    public function charge(string $paymentId): Response
    {
        $payment = $this->payments->findById(new PaymentId($paymentId));
        if ($payment === null) {
            return $this->redirectBack('error');
        }

        sleep(1);

        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $payment->order()->id()->toRfc4122(),
        ));

        return $this->redirectBack('success', $payment->order()->id()->toRfc4122());
    }

    #[Route('/{paymentId}/decline', name: 'decline', methods: ['POST'])]
    public function decline(string $paymentId): Response
    {
        $this->commandBus->dispatch(new FailPaymentCommand($paymentId));

        return $this->redirectBack('failed');
    }

    private function redirectBack(string $result, ?string $orderId = null): RedirectResponse
    {
        $target = $orderId !== null
            ? sprintf('%s/orders/%s?payment=%s', $this->frontendUrl, $orderId, $result)
            : sprintf('%s?payment=%s', $this->frontendUrl, $result);

        return new RedirectResponse($target);
    }
}
