<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Venue;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\VenueId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineVenueRepository implements VenueRepositoryInterface
{
    /** @var EntityRepository<Venue> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Venue::class);
    }

    public function findById(VenueId $id): ?Venue
    {
        return $this->entityManager->find(Venue::class, $id->toString());
    }

    /**
     * @return array<int, Venue>
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function save(Venue $venue): void
    {
        $this->entityManager->persist($venue);
    }
}
