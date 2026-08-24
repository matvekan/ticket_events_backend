<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\OutboxMessage;
use App\Domain\Repository\OutboxMessageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineOutboxMessageRepository implements OutboxMessageRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return OutboxMessage[] */
    public function findPending(int $limit): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from(OutboxMessage::class, 'm')
            ->where('m.sentAt IS NULL')
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
