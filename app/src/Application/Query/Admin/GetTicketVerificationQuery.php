<?php declare(strict_types=1);

namespace App\Application\Query\Admin;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GetTicketVerificationQuery implements QueryInterface
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^TKT-[A-Z0-9]{8}$/', message: 'Invalid ticket code format.')]
    public string $code {
        set => strtoupper(trim($value));
    }

    public function __construct(string $code)
    {
        $this->code = $code;
    }
}
