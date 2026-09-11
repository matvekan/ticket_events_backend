<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class TicketVerificationDto
{
    public function __construct(
        public bool $valid,
        public ?string $reason = null,
        public ?array $ticket = null,
    ) {
    }
}
