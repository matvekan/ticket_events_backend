<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\Email;

final class EmailType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return Email::class;
    }

    public function getName(): string
    {
        return 'email';
    }
}
