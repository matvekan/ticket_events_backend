<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/{id}/pay', name: 'order.pay', methods: ['POST'])]
final class PayOrderController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $userId = $this->security->getUser()?->id()?->toRfc4122();

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $id,
            userId: $userId,
        ));

        // Ownership is enforced inside the projection.
        $payment = $this->queryBus->dispatch(new GetPaymentForOrderQuery($id, $userId));

        return new JsonResponse([
            'paymentId' => $payment->id,
            'paymentUrl' => sprintf('/mock-bank/%s', $payment->id),
            'status' => $payment->status,
        ]);
    }
}