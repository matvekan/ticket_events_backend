<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Seat;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\SeatStatus;
use App\Domain\ValueObject\VenueId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineSeatRepository implements SeatRepositoryInterface
{
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Seat::class);
    }

    public function findById(SeatId $id): ?Seat
    {
        return $this->entityManager->find(Seat::class, $id->toString());
    }

    public function findByVenueId(VenueId $venueId): array
    {
        return $this->repository->findBy(['venueId' => $venueId->toString()]);
    }

    public function findByIds(array $ids): array
    {
        $stringIds = array_map(fn (SeatId $id) => $id->toString(), $ids);

        return $this->repository->findBy(['id' => $stringIds]);
    }

    public function findAvailableByEventId(EventId $eventId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Seat::class, 's')
            ->innerJoin(EventSeat::class, 'es', 'WITH', 'es.seat = s')
            ->where('es.event = :eventId')
            ->andWhere('es.status = :status')
            ->setParameter('eventId', $eventId->toString())
            ->setParameter('status', SeatStatus::Free)
            ->getQuery()
            ->getResult();
    }

    public function saveAll(array $seats): void
    {
        foreach ($seats as $seat) {
            $this->entityManager->persist($seat);
        }
    }
}
