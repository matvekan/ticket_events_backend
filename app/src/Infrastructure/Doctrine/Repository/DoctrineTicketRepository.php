<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Ticket;
use App\Domain\Repository\TicketRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineTicketRepository extends AbstractDoctrineRepository implements TicketRepositoryInterface
{
    private const ENTITY = Ticket::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?Ticket
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findByOrderId(Uuid $orderId): array
    {
        return $this->em()
            ->getRepository(self::ENTITY)
            ->findBy(['order' => $orderId]);
    }

    public function save(Ticket $ticket): void
    {
        $this->saveAndFlush($ticket);
    }
}
