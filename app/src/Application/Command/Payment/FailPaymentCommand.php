<?php

declare(strict_types=1);

namespace App\Application\Command\Payment;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class FailPaymentCommand implements CommandInterface
{
    public readonly Uuid $parsedPaymentId;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $paymentId,
    ) {
        $this->parsedPaymentId = Uuid::fromString($paymentId);
    }

    public function paymentId(): Uuid
    {
        return $this->parsedPaymentId;
    }
}