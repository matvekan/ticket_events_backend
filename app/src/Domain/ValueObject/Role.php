<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum Role: string
{
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
    case Manager = 'ROLE_MANAGER';
}
