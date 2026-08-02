<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\ValueObject\SeatStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineEventSeatRepository implements EventSeatRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?EventSeat
    {
        return $this->entityManager->find(EventSeat::class, $id);
    }

    public function findByEventId(Uuid $eventId): array
    {
        return $this->entityManager
            ->getRepository(EventSeat::class)
            ->findBy(['event' => $eventId]);
    }

    public function lockAndFindByIds(array $ids): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('es')
            ->from(EventSeat::class, 'es')
            ->where($qb->expr()->in('es.id', ':ids'))
            ->setParameter('ids', $ids);

        $query = $qb->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        return $query->getResult();
    }

    public function findAvailableByEventId(Uuid $eventId): array
    {
        return $this->entityManager
            ->getRepository(EventSeat::class)
            ->findBy(['event' => $eventId, 'status' => SeatStatus::Free]);
    }

    public function save(EventSeat $eventSeat): void
    {
        $this->entityManager->persist($eventSeat);
        $this->entityManager->flush();
    }
}
