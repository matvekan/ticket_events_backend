<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Order;

use App\Application\Dto\PaymentDto;
use App\Application\Query\Order\GetPaymentForOrderQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\OrderId;
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
        $payment = $this->payments->findByOrderId(new OrderId($query->orderId()->toRfc4122()));
        if ($payment === null) {
            throw new EntityNotFoundException('Payment not found.');
        }

        return new PaymentDto(
            id: $payment->id()->toRfc4122(),
            status: $payment->status()->value,
            amount: $payment->amount(),
        );
    }
}