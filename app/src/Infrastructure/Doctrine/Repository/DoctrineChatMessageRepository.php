<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\ChatRoom;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineChatMessageRepository implements ChatMessageRepositoryInterface
{
    /** @var EntityRepository<ChatMessage> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(ChatMessage::class);
    }

    public function findByRoomId(ChatRoomId $roomId): array
    {
        return $this->repository->findBy(['room' => $roomId->toString()], ['createdAt' => 'ASC']);
    }

    public function findLatestForRooms(array $rooms): array
    {
        if ($rooms === []) {
            return [];
        }

        $messages = $this->entityManager
            ->createQueryBuilder()
            ->select('m')
            ->from(ChatMessage::class, 'm')
            ->where('m.room IN (:rooms)')
            ->andWhere('m.createdAt = (' .
                'SELECT MAX(m2.createdAt) FROM ' . ChatMessage::class . ' m2 WHERE m2.room = m.room)')
            ->setParameter('rooms', $rooms)
            ->getQuery()
            ->getResult();

        $latest = [];
        foreach ($messages as $message) {
            if ($message instanceof ChatMessage) {
                $latest[$message->room()->id()->toString()] = $message;
            }
        }

        return $latest;
    }

    public function save(ChatMessage $message): void
    {
        $this->entityManager->persist($message);
    }
}
