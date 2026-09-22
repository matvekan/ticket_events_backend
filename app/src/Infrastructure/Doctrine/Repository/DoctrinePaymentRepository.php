<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Dto\PaymentDto;
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
        return $this->repository->findOneBy(['orderId' => $orderId->toString()]);
    }

    public function findDetailsByOrderId(string $orderId, ?string $userId): ?PaymentDto
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb->select('p.id, p.status, p.amount')
            ->from('payments', 'p')
            ->join('p', 'orders', 'o', 'o.id = p.order_id')
            ->where('p.order_id = :orderId')
            ->setParameter('orderId', $orderId);

        if ($userId !== null) {
            $qb->andWhere('o.user_id = :userId')
                ->setParameter('userId', $userId);
        }

        $row = $qb->executeQuery()->fetchAssociative();

        if ($row === false) {
            return null;
        }
        assert(is_string($row['id']));
        assert(is_string($row['status']));
        assert(is_int($row['amount']) || is_string($row['amount']));

        return new PaymentDto(
            id: $row['id'],
            status: $row['status'],
            amount: (int) $row['amount'],
        );
    }

    public function save(Payment $payment): void
    {
        $this->entityManager->persist($payment);
    }
}
