<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\PaymentDto;
use App\Application\Exception\EntityNotFoundException as ApplicationEntityNotFoundException;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetPaymentForOrderHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
    ) {
    }

    public function __invoke(GetPaymentForOrderQuery $query): PaymentDto
    {
        $payment = $this->payments->findDetailsByOrderId($query->orderId, $query->userId);

        if ($payment === null) {
            throw new ApplicationEntityNotFoundException('Payment not found.');
        }

        return $payment;
    }
}
