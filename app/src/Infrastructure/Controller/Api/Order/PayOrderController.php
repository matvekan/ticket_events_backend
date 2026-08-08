<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PaymentRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/orders/{id}/pay', name: 'order.pay', methods: ['POST'])]
final class PayOrderController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly PaymentRepositoryInterface $payments,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $orderId = Uuid::fromRfc4122($id);

        $this->commandBus->dispatch(new StartPaymentCommand(
            $orderId,
            $this->security->getUser()?->id(),
        ));

        $payment = $this->payments->findByOrderId($orderId);
        if ($payment === null) {
            throw new EntityNotFoundException('Payment not found.');
        }

        return new JsonResponse([
            'paymentId' => $payment->id()->toRfc4122(),
            'paymentUrl' => sprintf('/mock-bank/%s', $payment->id()->toRfc4122()),
            'status' => 'payment_pending',
        ]);
    }
}
