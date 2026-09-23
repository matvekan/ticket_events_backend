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

    /**
     * @return array<int, OutboxMessage>
     */
    public function findPending(int $limit): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('messenger_outbox')
            ->where('sent_at IS NULL')
            ->orderBy('created_at', 'ASC')
            ->setMaxResults($limit);

        $sql = $qb->getSQL().' FOR UPDATE SKIP LOCKED';
        $ids = $this->connection->fetchFirstColumn($sql);

        if ($ids === []) {
            return [];
        }

        /** @var array<int, OutboxMessage> $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(OutboxMessage::class, 'm')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        return $result;
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
