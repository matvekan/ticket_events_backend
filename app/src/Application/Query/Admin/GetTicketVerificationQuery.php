<?php

declare(strict_types=1);

namespace App\Application\Query\Admin;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GetTicketVerificationQuery implements QueryInterface
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^TKT-[A-Z0-9]{8}$/', message: 'Invalid ticket code format.')]
    public readonly string $code;

    public function __construct(
        string $code,
    ) {
        $this->code = strtoupper(trim($code));
    }
}