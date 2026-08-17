<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Seat;
use App\Domain\Repository\SeatRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrineSeatRepository implements SeatRepositoryInterface
{
    /** @var EntityRepository<Seat> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Seat::class);
    }

    public function findById(Uuid $id): ?Seat
    {
        return $this->entityManager->find(Seat::class, $id);
    }

    public function findByVenueId(Uuid $venueId): array
    {
        return $this->repository->findBy(['venue' => $venueId]);
    }

    public function saveAll(array $seats): void
    {
        foreach ($seats as $seat) {
            $this->entityManager->persist($seat);
        }
    }
}
