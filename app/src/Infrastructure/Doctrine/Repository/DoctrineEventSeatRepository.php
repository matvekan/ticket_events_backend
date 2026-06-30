<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\ValueObject\SeatStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineEventSeatRepository extends AbstractDoctrineRepository implements EventSeatRepositoryInterface
{
    private const ENTITY = EventSeat::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?EventSeat
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findByEventId(Uuid $eventId): array
    {
        return $this->em()
            ->getRepository(self::ENTITY)
            ->findBy(['event' => $eventId]);
    }

    public function lockAndFindByIds(array $ids): array
    {
        $qb = $this->em()->createQueryBuilder();
        $qb->select('es')
            ->from(self::ENTITY, 'es')
            ->where($qb->expr()->in('es.id', ':ids'))
            ->setParameter('ids', $ids);

        $query = $qb->getQuery();
        $query->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

        return $query->getResult();
    }

    public function findAvailableByEventId(Uuid $eventId): array
    {
        return $this->em()
            ->getRepository(self::ENTITY)
            ->findBy(['event' => $eventId, 'status' => SeatStatus::Free]);
    }

    public function save(EventSeat $eventSeat): void
    {
        $this->saveAndFlush($eventSeat);
    }
}
