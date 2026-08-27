<?php

declare(strict_types=1);

namespace App\Application\Command\Payment;

use App\Application\Command\CommandInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Mirrors ConfirmPaymentCommand: payments are addressed by order id.
 */
final class FailPaymentCommand implements CommandInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $orderId,

        #[Assert\Uuid]
        public readonly ?string $userId = null,
    ) {
    }
}
