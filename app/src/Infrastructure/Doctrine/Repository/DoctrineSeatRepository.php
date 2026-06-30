<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Seat;
use App\Domain\Repository\SeatRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineSeatRepository extends AbstractDoctrineRepository implements SeatRepositoryInterface
{
    private const ENTITY = Seat::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?Seat
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findByVenueId(Uuid $venueId): array
    {
        return $this->em()
            ->getRepository(self::ENTITY)
            ->findBy(['venue' => $venueId]);
    }

    public function save(Seat $seat): void
    {
        $this->saveAndFlush($seat);
    }
}
