<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Ticket;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\TicketCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineTicketRepository implements TicketRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Ticket
    {
        return $this->entityManager->find(Ticket::class, $id);
    }

    public function findByCode(string $code): ?Ticket
    {
        return $this->entityManager
            ->getRepository(Ticket::class)
            ->findOneBy(['code' => new TicketCode($code)]);
    }

    public function findByOrderId(Uuid $orderId): array
    {
        return $this->entityManager
            ->getRepository(Ticket::class)
            ->findBy(['order' => $orderId]);
    }

    public function save(Ticket $ticket): void
    {
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();
    }
}
