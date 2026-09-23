<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryBusInterface;
use App\Application\Service\Payment\StripePaymentService;
use App\Infrastructure\Controller\Api\Shared\RequiresDomainUserTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders/{id}/pay', name: 'order.pay', methods: ['POST'])]
final class PayOrderController
{
    use RequiresDomainUserTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
        private readonly StripePaymentService $stripeService,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $userId = $this->getDomainUser($this->security)->id()->toString();

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $id,
            userId: $userId,
        ));

        $payment = $this->queryBus->dispatch(new GetPaymentForOrderQuery($id, $userId));
        assert($payment instanceof \App\Application\Dto\PaymentDto);

        $session = $this->stripeService->createCheckoutSession($id, $payment->id, $payment->amount);

        return new JsonResponse([
            'paymentId' => $payment->id,
            'paymentUrl' => $session->url,
            'status' => $payment->status,
        ]);
    }
}
