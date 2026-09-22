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

    /**
     * @return array<int, Order>
     */
    public function findByUserId(UserId $userId): array
    {
        return $this->repository->findBy(['userId' => $userId->toString()], ['createdAt' => 'DESC']);
    }

    /**
     * @return array<int, Order>
     */
    public function findPendingExpired(\DateTimeImmutable $cutoff): array
    {
        $result = $this->repository
            ->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt < :cutoff')
            ->setParameter('status', OrderStatus::Pending)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
        assert(is_array($result));

        return $result;
    }

    /**
     * @return array<int, Order>
     */
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

        $result = $this->repository->createQueryBuilder('o')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $rows)
            ->getQuery()
            ->getResult();
        assert(is_array($result));

        return $result;
    }

    public function findByIdAndUser(string $orderId, ?string $userId): ?OrderDto
    {
        $rows = $this->getOrderRows($orderId, $userId);
        if ($rows === []) {
            return null;
        }

        return $this->hydrate($rows);
    }

    /**
     * @return array<int, OrderDto>
     */
    public function findOrderByUserId(string $userId): array
    {
        $rows = $this->getOrderRowsForUser($userId);
        if ($rows === []) {
            return [];
        }

        /** @var array<string, array<int, array<string, mixed>>> $ordersById */
        $ordersById = [];
        foreach ($rows as $row) {
            assert(is_string($row['id']));
            $ordersById[$row['id']][] = $row;
        }

        return array_map(fn (array $orderRows) => $this->hydrate($orderRows), array_values($ordersById));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function hydrate(array $rows): OrderDto
    {
        $first = $rows[0];
        assert(is_string($first['id']));
        assert(is_string($first['status']));
        assert(is_string($first['total_currency']));

        return new OrderDto(
            id: $first['id'],
            status: $first['status'],
            total: (int) $first['total_amount'],
            totalCurrency: $first['total_currency'],
            createdAt: $this->formatDate($first['created_at']),
            tickets: array_values(array_filter(array_map(
                fn (array $row): ?TicketDto => $row['ticket_id'] !== null ? $this->hydrateTicket($row) : null,
                $rows,
            ))),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateTicket(array $row): TicketDto
    {
        assert(is_string($row['ticket_id']));
        assert(is_string($row['ticket_code']));
        assert(is_string($row['event_seat_id']));
        assert(is_string($row['price_currency']));
        assert(is_string($row['ticket_status']));
        if ($row['event_title'] !== null) {
            assert(is_string($row['event_title']));
        }
        if ($row['venue_name'] !== null) {
            assert(is_string($row['venue_name']));
        }

        return new TicketDto(
            id: $row['ticket_id'],
            code: $row['ticket_code'],
            eventSeatId: $row['event_seat_id'],
            eventTitle: $row['event_title'] !== null ? $row['event_title'] : '',
            eventDate: isset($row['event_date'])
                ? $this->formatDate($row['event_date'])
                : '',
            venueName: $row['venue_name'] !== null ? $row['venue_name'] : '',
            priceAmount: (int) $row['price_amount'],
            priceCurrency: $row['price_currency'],
            status: $row['ticket_status'],
        );
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value->format('c');
        }
        assert(is_string($value));

        return (new \DateTimeImmutable($value))->format('c');
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
    }
}
