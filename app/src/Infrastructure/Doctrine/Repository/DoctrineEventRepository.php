<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Event;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\ValueObject\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineEventRepository extends AbstractDoctrineRepository implements EventRepositoryInterface
{
    private const ENTITY = Event::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?Event
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findAllPublished(): array
    {
        return $this->em()
            ->getRepository(self::ENTITY)
            ->findBy(['status' => EventStatus::Published]);
    }

    public function save(Event $event): void
    {
        $this->saveAndFlush($event);
    }
}
