<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Dto\TicketVerificationData;
use App\Domain\Entity\Ticket;
use App\Domain\Repository\TicketRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Domain\ValueObject\TicketCode;

final class DoctrineTicketRepository implements TicketRepositoryInterface
{

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

    public function findByCode(TicketCode $code): ?Ticket
    {
        return $this->repository->findOneBy(['code' => $code->toString()]);
    }

    public function findVerificationByCode(string $code): ?TicketVerificationData
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();

        $qb->select(
            't.code',
            'o.status AS order_status',
            'e.status AS event_status',
            'e.title AS event_title',
            'e.date AS event_date',
            'v.name AS venue_name',
            's.row AS seat_row',
            's.number AS seat_number'
        )
            ->from('tickets', 't')
            ->join('t', 'orders', 'o', 'o.id = t.order_id')
            ->join('t', 'event_seats', 'es', 'es.id = t.event_seat_id')
            ->join('es', 'events', 'e', 'e.id = es.event_id')
            ->join('e', 'venues', 'v', 'v.id = e.venue_id')
            ->join('es', 'seats', 's', 's.id = es.seat_id')
            ->where('t.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1);

        $row = $qb->executeQuery()->fetchAssociative();

        if ($row === false) {
            return null;
        }

        $eventDate = $row['event_date'] instanceof \DateTimeImmutable
            ? $row['event_date']
            : new \DateTimeImmutable((string) $row['event_date']);

        return new TicketVerificationData(
            code: (string) $row['code'],
            orderStatus: (string) $row['order_status'],
            eventStatus: (string) $row['event_status'],
            eventTitle: (string) $row['event_title'],
            eventDate: $eventDate->format('Y-m-d H:i:s'),
            venueName: (string) $row['venue_name'],
            seatRow: (string) $row['seat_row'],
            seatNumber: (int) $row['seat_number'],
        );
    }

    public function save(Ticket $ticket): void
    {
        $this->entityManager->persist($ticket);
    }
}
