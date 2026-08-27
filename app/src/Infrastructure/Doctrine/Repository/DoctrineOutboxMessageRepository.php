<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\OutboxMessage;
use App\Domain\Repository\OutboxMessageRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOutboxMessageRepository implements OutboxMessageRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
    }

    /** @return OutboxMessage[] */
    public function findPending(int $limit): array
    {
        // Competing workers must not publish the same rows. FOR UPDATE SKIP LOCKED
        // lets each worker claim a disjoint batch inside a single transaction.
        $conn = $this->connection;
        $conn->beginTransaction();

        try {
            $ids = $conn->fetchFirstColumn(
                'SELECT id FROM messenger_outbox WHERE sent_at IS NULL ORDER BY created_at ASC LIMIT ' . ((int) $limit) . ' FOR UPDATE SKIP LOCKED',
            );

            if ($ids === []) {
                $conn->commit();

                return [];
            }

            // Hydrate the locked rows through the UnitOfWork so the caller can
            // mutate (markSent/markFailed) and flush them afterwards.
            $entities = $this->entityManager->createQueryBuilder()
                ->select('m')
                ->from(OutboxMessage::class, 'm')
                ->where('m.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();

            $conn->commit();

            return $entities;
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }

            throw $e;
        }
    }

    public function save(OutboxMessage $message): void
    {
        $this->entityManager->persist($message);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
