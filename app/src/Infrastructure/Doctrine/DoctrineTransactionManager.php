<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Application\Exception\PersistenceConstraintViolationException;
use App\Application\Transaction\TransactionManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $fn): mixed
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($fn): mixed {
                $result = $fn();
                $this->entityManager->flush();

                return $result;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw new PersistenceConstraintViolationException($exception->getMessage(), 0, $exception);
        }
    }
}
