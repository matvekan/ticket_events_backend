<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\TicketCode;

final class TicketCodeType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return TicketCode::class;
    }

    public function getName(): string
    {
        return 'ticket_code';
    }
}
