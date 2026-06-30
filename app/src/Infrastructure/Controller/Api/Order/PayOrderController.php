<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\ConfirmPaymentCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/orders/{id}/pay', name: 'order.pay', methods: ['POST'])]
final class PayOrderController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new ConfirmPaymentCommand(Uuid::fromRfc4122($id)));

        return new JsonResponse(['message' => 'Payment confirmed.']);
    }
}
