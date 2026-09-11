<?php

declare(strict_types=1);

namespace App\Application\Command\Admin;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ScanTicketCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $ticketCode,
    ) {
    }
}
