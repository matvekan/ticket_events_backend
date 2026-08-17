<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Payment;
use App\Domain\Repository\PaymentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

final class DoctrinePaymentRepository implements PaymentRepositoryInterface
{
    /** @var EntityRepository<Payment> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Payment::class);
    }

    public function findById(Uuid $id): ?Payment
    {
        return $this->entityManager->find(Payment::class, $id);
    }

    public function findByOrderId(Uuid $orderId): ?Payment
    {
        return $this->repository->findOneBy(['order' => $orderId]);
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }
}