<?php

declare(strict_types=1);

namespace App\Application\Query\Admin;

use App\Application\Query\QueryInterface;

final class GetTicketVerificationQuery implements QueryInterface
{
    public function __construct(
        public readonly string $code,
    ) {
    }
}