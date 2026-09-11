<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryBusInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
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
        $userId = $this->security->getUser()?->id()?->toString();

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $id,
            userId: $userId,
        ));

        $payment = $this->queryBus->dispatch(new GetPaymentForOrderQuery($id, $userId));

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'byn', // Валюта
                    'product_data' => [
                        'name' => 'Билеты на мероприятие (Заказ #' . substr($id, 0, 8) . ')',
                    ],
                    'unit_amount' => $payment->amount,
                ],
                'quantity' => 1,
            ]],
            'success_url' => 'http://localhost:5173/orders/' . $id . '?payment=success',
            'cancel_url' => 'http://localhost:5173/orders/' . $id . '?payment=failed',

            'client_reference_id' => $payment->id,
        ]);

        return new JsonResponse([
            'paymentId' => $payment->id,
            'paymentUrl' => $session->url,
            'status' => $payment->status,
        ]);
    }
}
