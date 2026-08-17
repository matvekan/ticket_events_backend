<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\ValueObject\SeatStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrineEventSeatRepository implements EventSeatRepositoryInterface
{
    /** @var EntityRepository<EventSeat> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(EventSeat::class);
    }

    public function findById(Uuid $id): ?EventSeat
    {
        return $this->entityManager->find(EventSeat::class, $id);
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
        return $this->repository->findBy(['event' => $eventId, 'status' => SeatStatus::Free]);
    }
}