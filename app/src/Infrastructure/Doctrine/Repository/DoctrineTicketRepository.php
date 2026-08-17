<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Ticket;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\TicketCode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrineTicketRepository implements TicketRepositoryInterface
{
    /** @var EntityRepository<Ticket> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Ticket::class);
    }

    public function findByCode(string $code): ?Ticket
    {
        return $this->repository->findOneBy(['code' => new TicketCode($code)]);
    }

    public function findByOrderId(Uuid $orderId): array
    {
        return $this->repository->findBy(['order' => $orderId]);
    }
}