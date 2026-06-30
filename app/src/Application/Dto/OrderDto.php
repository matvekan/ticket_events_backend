<?php

declare(strict_types=1);

namespace App\Application\Dto;

final class OrderDto
{
    /** @param TicketDto[] $tickets */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly float $total,
        public readonly string $createdAt,
        public readonly array $tickets,
    ) {
    }
}
