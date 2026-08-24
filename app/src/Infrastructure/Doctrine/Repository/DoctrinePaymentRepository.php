<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Payment;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrinePaymentRepository implements PaymentRepositoryInterface
{
    /** @var EntityRepository<Payment> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Payment::class);
    }

    public function findById(PaymentId $id): ?Payment
    {
        return $this->entityManager->find(Payment::class, $id->toString());
    }

    public function findByOrderId(OrderId $orderId): ?Payment
    {
        return $this->repository->findOneBy(['order' => $orderId->toString()]);
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }
}
