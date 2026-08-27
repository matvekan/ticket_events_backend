<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\PaymentDto;
use App\Application\Exception\EntityNotFoundException as ApplicationEntityNotFoundException;
use App\Application\Port\PaymentReadRepositoryInterface;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPaymentForOrderHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PaymentReadRepositoryInterface $payments,
    ) {
    }

    public function __invoke(GetPaymentForOrderQuery $query): PaymentDto
    {
        // Ownership enforced in the projection: foreign payments read as "not found".
        $payment = $this->payments->findByOrderId($query->orderId, $query->userId);

        if ($payment === null) {
            throw new ApplicationEntityNotFoundException('Payment not found.');
        }

        return $payment;
    }
}
