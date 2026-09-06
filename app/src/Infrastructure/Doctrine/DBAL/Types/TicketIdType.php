<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\TicketId;

final class TicketIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return TicketId::class;
    }

    public function getName(): string
    {
        return 'ticket_id';
    }
}
