<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Seat;
use App\Domain\Repository\SeatRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineSeatRepository implements SeatRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Seat
    {
        return $this->entityManager->find(Seat::class, $id);
    }

    public function findByVenueId(Uuid $venueId): array
    {
        return $this->entityManager
            ->getRepository(Seat::class)
            ->findBy(['venue' => $venueId]);
    }

    public function save(Seat $seat): void
    {
        $this->entityManager->persist($seat);
        $this->entityManager->flush();
    }

    public function saveAll(array $seats): void
    {
        foreach ($seats as $seat) {
            $this->entityManager->persist($seat);
        }

        $this->entityManager->flush();
    }
}
