<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Application\Transaction\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $fn): void
    {
        $this->entityManager->wrapInTransaction($fn);
    }
}
