<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\DBAL\Types;

use App\Domain\ValueObject\Name;

final class NameType extends AbstractValueObjectType
{
    protected function getValueObjectClass(): string
    {
        return Name::class;
    }

    public function getName(): string
    {
        return 'name';
    }
}
