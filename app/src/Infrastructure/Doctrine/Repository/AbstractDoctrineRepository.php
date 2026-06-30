<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractDoctrineRepository
{
    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    protected function em(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    protected function flush(): void
    {
        $this->entityManager->flush();
    }

    protected function saveAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function transactional(callable $fn): void
    {
        $this->entityManager->wrapInTransaction($fn);
    }
}
