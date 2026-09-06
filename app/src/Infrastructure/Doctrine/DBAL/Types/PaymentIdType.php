<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\PaymentId;

final class PaymentIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return PaymentId::class;
    }

    public function getName(): string
    {
        return 'payment_id';
    }
}
