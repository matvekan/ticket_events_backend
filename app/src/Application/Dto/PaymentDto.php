<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class PaymentDto
{
    public function __construct(
        public string $id,
        public string $status,
        public int $amount,
    ) {
    }
}
