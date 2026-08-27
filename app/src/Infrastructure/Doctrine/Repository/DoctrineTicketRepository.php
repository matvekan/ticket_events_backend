<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Ticket;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineTicketRepository implements TicketRepositoryInterface
{
    /** @var EntityRepository<Ticket> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Ticket::class);
    }

    public function findByOrderId(OrderId $orderId): array
    {
        return $this->repository->findBy(['order' => $orderId->toString()]);
    }
}
