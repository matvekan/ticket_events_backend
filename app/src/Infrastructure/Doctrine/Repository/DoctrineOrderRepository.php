<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(Uuid $id): ?Order
    {
        return $this->entityManager->find(Order::class, $id);
    }

    public function findByUserId(Uuid $userId): array
    {
        return $this->entityManager
            ->getRepository(Order::class)
            ->findBy(['user' => $userId], ['createdAt' => 'DESC']);
    }

    public function findPendingExpired(\DateTimeImmutable $cutoff): array
    {
        return $this->entityManager
            ->getRepository(Order::class)
            ->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt < :cutoff')
            ->setParameter('status', OrderStatus::Pending)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
