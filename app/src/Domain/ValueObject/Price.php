<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class Price
{
    private int $amount;
    private string $currency;

    private function __construct(int $amount, string $currency = 'BYN')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Price amount must be non-negative.');
        }

        if (strtoupper($currency) !== 'BYN') {
            throw new \InvalidArgumentException('Only BYN currency is supported in this system.');
        }

        $this->amount = $amount;
        $this->currency = 'BYN';
    }

    public static function fromAmount(int $amount, string $currency = 'BYN'): self
    {
        return new self($amount, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
