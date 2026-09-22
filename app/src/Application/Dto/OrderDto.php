<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class OrderDto
{
    /**
     * @param array<int, TicketDto> $tickets
     */
    public function __construct(
        public string $id,
        public string $status,
        public int $total,
        public string $totalCurrency,
        public string $createdAt,
        public array $tickets,
    ) {
    }
}
