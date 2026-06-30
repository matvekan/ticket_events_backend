<?php

declare(strict_types=1);

namespace App\Application\Transaction;

interface TransactionManagerInterface
{
    public function transactional(callable $fn): void;
}
