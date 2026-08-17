<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class Price
{
    private int $amount;
    private string $currency;

    private function __construct(int $amount, string $currency = 'RUB')
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Price amount must be non-negative.');
        }

        if (mb_strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a 3-letter ISO code.');
        }

        $this->amount = $amount;
        $this->currency = strtoupper($currency);
    }

    public static function fromAmount(int $amount, string $currency = 'RUB'): self
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
