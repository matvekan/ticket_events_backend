<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Dto\PaymentDto;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryBusInterface;
use App\Application\Service\Payment\StripePaymentService;
use App\Infrastructure\Security\DomainUserAdapter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/orders/{id}/pay', name: 'order.pay', methods: ['POST'])]
final class PayOrderController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
        private readonly StripePaymentService $stripeService,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $user = $this->security->getUser();
        $userId = null;
        if ($user instanceof DomainUserAdapter) {
            $userId = $user->id()->toString();
        } elseif ($user !== null) {
            throw new AccessDeniedException('Access denied.');
        }

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $id,
            userId: $userId,
        ));

        $payment = $this->queryBus->dispatch(new GetPaymentForOrderQuery($id, $userId));
        assert($payment instanceof PaymentDto);

        $session = $this->stripeService->createCheckoutSession($id, $payment->id, $payment->amount);

        return new JsonResponse([
            'paymentId' => $payment->id,
            'paymentUrl' => $session->url,
            'status' => $payment->status,
        ]);
    }
}
