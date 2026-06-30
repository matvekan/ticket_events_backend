<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineOrderRepository extends AbstractDoctrineRepository implements OrderRepositoryInterface
{
    private const ENTITY = Order::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?Order
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findByUserId(Uuid $userId): array
    {
        return $this->em()
            ->getRepository(self::ENTITY)
            ->findBy(['user' => $userId]);
    }

    public function save(Order $order): void
    {
        $this->saveAndFlush($order);
    }
}
