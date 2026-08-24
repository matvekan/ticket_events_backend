<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineOrderRepository implements OrderRepositoryInterface
{
    /** @var EntityRepository<Order> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Order::class);
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->entityManager->find(Order::class, $id->toString());
    }

    public function findByUserId(UserId $userId): array
    {
        return $this->repository->findBy(['userId' => $userId->toString()], ['createdAt' => 'DESC']);
    }

    public function findPendingExpired(\DateTimeImmutable $cutoff): array
    {
        return $this->repository
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
    }
}
