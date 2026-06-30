<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Venue;
use App\Domain\Repository\VenueRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineVenueRepository extends AbstractDoctrineRepository implements VenueRepositoryInterface
{
    private const ENTITY = Venue::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?Venue
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findAll(): array
    {
        return $this->em()->getRepository(self::ENTITY)->findAll();
    }

    public function save(Venue $venue): void
    {
        $this->saveAndFlush($venue);
    }
}
