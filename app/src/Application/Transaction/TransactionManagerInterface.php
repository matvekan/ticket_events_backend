<?php

declare(strict_types=1);

namespace App\Application\Transaction;

interface TransactionManagerInterface
{
    /**
     * Executes the callable inside a database transaction.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function transactional(callable $fn): mixed;
}
