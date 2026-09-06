<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\UserId;

final class UserIdType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return UserId::class;
    }

    public function getName(): string
    {
        return 'user_id';
    }
}
