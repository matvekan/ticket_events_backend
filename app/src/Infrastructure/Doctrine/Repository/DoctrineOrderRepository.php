<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Dto\OrderDto;
use App\Application\Dto\TicketDto;
use App\Domain\Entity\Order;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\OrderStatus;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineOrderRepository implements OrderRepositoryInterface
{
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

    public function findByEventId(EventId $eventId): array
    {
        $conn = $this->entityManager->getConnection();
        $rows = $conn->createQueryBuilder()
            ->select('DISTINCT o.id')
            ->from('orders', 'o')
            ->innerJoin('o', 'tickets', 't', 't.order_id = o.id')
            ->innerJoin('o', 'event_seats', 'es', 'es.id = t.event_seat_id')
            ->where('es.event_id = :eventId')
            ->setParameter('eventId', $eventId->toString())
            ->executeQuery()
            ->fetchFirstColumn();

        if ($rows === []) {
            return [];
        }

        return $this->repository->createQueryBuilder('o')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $rows)
            ->getQuery()
            ->getResult();
    }

    public function findByIdAndUser(string $orderId, ?string $userId): ?OrderDto
    {
        $rows = $this->getOrderRows($orderId, $userId);
        if ($rows === []) {
            return null;
        }
        return $this->hydrate($rows);
    }

    public function findByUserIdDto(string $userId): array
    {
        $rows = $this->getOrderRowsForUser($userId);
        if ($rows === []) {
            return [];
        }

        $ordersById = [];
        foreach ($rows as $row) {
            $ordersById[$row['id']][] = $row;
        }

        return array_map(fn(array $orderRows) => $this->hydrate($orderRows), array_values($ordersById));
    }

    private function getOrderRows(string $orderId, ?string $userId): array
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb->select(
            'o.id, o.status, o.total_amount, o.total_currency, o.created_at,
             t.id AS ticket_id, t.code AS ticket_code, t.status AS ticket_status,
             t.event_seat_id, t.price_amount, t.price_currency,
             e.title AS event_title, e.date AS event_date, v.name AS venue_name'
        )
            ->from('orders', 'o')
            ->leftJoin('o', 'tickets', 't', 't.order_id = o.id')
            ->leftJoin('o', 'event_seats', 'es', 'es.id = t.event_seat_id')
            ->leftJoin('o', 'events', 'e', 'e.id = es.event_id')
            ->leftJoin('o', 'venues', 'v', 'v.id = e.venue_id')
            ->where('o.id = :orderId')
            ->setParameter('orderId', $orderId);

        if ($userId !== null) {
            $qb->andWhere('o.user_id = :userId')
                ->setParameter('userId', $userId);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function getOrderRowsForUser(string $userId): array
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb->select(
            'o.id, o.status, o.total_amount, o.total_currency, o.created_at,
             t.id AS ticket_id, t.code AS ticket_code, t.status AS ticket_status,
             t.event_seat_id, t.price_amount, t.price_currency,
             e.title AS event_title, e.date AS event_date, v.name AS venue_name'
        )
            ->from('orders', 'o')
            ->leftJoin('o', 'tickets', 't', 't.order_id = o.id')
            ->leftJoin('o', 'event_seats', 'es', 'es.id = t.event_seat_id')
            ->leftJoin('o', 'events', 'e', 'e.id = es.event_id')
            ->leftJoin('o', 'venues', 'v', 'v.id = e.venue_id')
            ->where('o.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('o.created_at', 'DESC');

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function hydrate(array $rows): OrderDto
    {
        $first = $rows[0];

        return new OrderDto(
            id: (string) $first['id'],
            status: (string) $first['status'],
            total: (int) $first['total_amount'],
            totalCurrency: (string) $first['total_currency'],
            createdAt: $this->formatDate($first['created_at']),
            tickets: array_values(array_filter(array_map(
                fn (array $row): ?TicketDto => $row['ticket_id'] !== null ? $this->hydrateTicket($row) : null,
                $rows,
            ))),
        );
    }

    private function hydrateTicket(array $row): TicketDto
    {
        return new TicketDto(
            id: (string) $row['ticket_id'],
            code: (string) $row['ticket_code'],
            eventSeatId: (string) $row['event_seat_id'],
            eventTitle: $row['event_title'] !== null ? (string) $row['event_title'] : '',
            eventDate: isset($row['event_date']) && $row['event_date'] !== null
                ? $this->formatDate($row['event_date'])
                : '',
            venueName: $row['venue_name'] !== null ? (string) $row['venue_name'] : '',
            priceAmount: (int) $row['price_amount'],
            priceCurrency: (string) $row['price_currency'],
        );
    }

    private function formatDate(mixed $value): string
    {
        return $value instanceof \DateTimeImmutable
            ? $value->format('c')
            : (new \DateTimeImmutable((string) $value))->format('c');
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
    }
}
