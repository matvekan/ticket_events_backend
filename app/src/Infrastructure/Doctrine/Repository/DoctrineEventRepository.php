<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Event;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\ValueObject\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineEventRepository implements EventRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Event
    {
        return $this->entityManager->find(Event::class, $id);
    }

    public function findAllPublished(): array
    {
        return $this->entityManager
            ->getRepository(Event::class)
            ->findBy(['status' => EventStatus::Published]);
    }

    public function save(Event $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
