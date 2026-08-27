<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use App\Application\Exception\PersistenceConstraintViolationException;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        private readonly IdGeneratorInterface $ids,
    ) {
    }

    public function transactional(callable $fn): mixed
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($fn): mixed {
                $result = $fn();

                // Automatically persist domain events to the Outbox within the
                // same DB transaction. Handlers return them via releaseEvents().
                if (is_array($result)) {
                    foreach ($result as $event) {
                        if (!is_object($event)) {
                            continue;
                        }

                        $this->entityManager->persist(new OutboxMessage(
                            $event::class,
                            base64_encode(serialize($event)),
                            $this->clock,
                            $this->ids,
                        ));
                    }
                }

                $this->entityManager->flush();

                return $result;
            });
        } catch (UniqueConstraintViolationException $exception) {
            // Translated so the Application layer never sees Doctrine types.
            throw new PersistenceConstraintViolationException(
                $exception->getMessage(),
                0,
                $exception,
            );
        }
    }
}
