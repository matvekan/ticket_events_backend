<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/{id}/pay', name: 'order.pay', methods: ['POST'])]
#[OA\Tag(name: 'Orders')]
#[OA\Parameter(name: 'id', description: 'Order UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Post(
    path: '/api/orders/{id}/pay',
    summary: 'Start payment for an order',
    tags: ['Orders'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Payment initiated', content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'paymentId', type: 'string', format: 'uuid'),
                new OA\Property(property: 'paymentUrl', type: 'string', example: '/mock-bank/...'),
                new OA\Property(property: 'chargeUrl', type: 'string', example: '/mock-bank/.../charge'),
                new OA\Property(property: 'declineUrl', type: 'string', example: '/mock-bank/.../decline'),
                new OA\Property(property: 'status', type: 'string'),
            ]
        )),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Order not found'),
        new OA\Response(response: 422, description: 'Order cannot be paid'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
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
        $userId = $this->security->getUser()?->id()?->toString();

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $id,
            userId: $userId,
        ));

        $payment = $this->queryBus->dispatch(new GetPaymentForOrderQuery($id, $userId));

        return new JsonResponse([
            'paymentId' => $payment->id,
            'paymentUrl' => sprintf('/mock-bank/%s', $payment->id),
            'chargeUrl' => sprintf('/mock-bank/%s/charge', $payment->id),
            'declineUrl' => sprintf('/mock-bank/%s/decline', $payment->id),
            'status' => $payment->status,
        ]);
    }
}
