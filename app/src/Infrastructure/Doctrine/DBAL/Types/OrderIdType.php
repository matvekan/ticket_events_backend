<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\OrderId;

final class OrderIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return OrderId::class;
    }

    public function getName(): string
    {
        return 'order_id';
    }
}
