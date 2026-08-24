<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Seat;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\VenueId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineSeatRepository implements SeatRepositoryInterface
{
    /** @var EntityRepository<Seat> */
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
        return $this->repository->findBy(['venue' => $venueId->toString()]);
    }

    public function saveAll(array $seats): void
    {
        foreach ($seats as $seat) {
            $this->entityManager->persist($seat);
        }
    }
}
