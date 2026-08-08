<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Venue;
use App\Domain\Repository\VenueRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineVenueRepository implements VenueRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Venue
    {
        return $this->entityManager->find(Venue::class, $id);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Venue::class)->findAll();
    }

    public function save(Venue $venue): void
    {
        $this->entityManager->persist($venue);
    }
}
