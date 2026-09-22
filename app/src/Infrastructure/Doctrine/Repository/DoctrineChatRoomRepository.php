<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Dto\ChatMessageDto;
use App\Application\Dto\ChatRoomDto;
use App\Domain\Entity\ChatRoom;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineChatRoomRepository implements ChatRoomRepositoryInterface
{
    /** @var EntityRepository<ChatRoom> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(ChatRoom::class);
    }

    public function findById(ChatRoomId $id): ?ChatRoom
    {
        return $this->entityManager->find(ChatRoom::class, $id->toString());
    }

    public function findByUserId(UserId $userId): ?ChatRoom
    {
        return $this->repository->findOneBy(['userId' => $userId->toString()]);
    }

    /**
     * @return array<int, ChatRoom>
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @return array<int, \App\Application\Dto\ChatRoomDto>
     */
    public function findSupportRooms(): array
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();

        $qb->select(
            'r.id',
            'r.user_id',
            'u.email AS user_email',
            'r.created_at',
            'lm.id AS last_message_id',
            'lm.text AS last_message_text',
            'lm.created_at AS last_message_created_at',
            'lm.sender_id AS last_message_sender_id',
            'lu.name AS last_message_sender_name',
            'lu.roles AS last_message_sender_roles'
        )
            ->from('chat_rooms', 'r')
            ->join('r', 'users', 'u', 'u.id = r.user_id')
            ->leftJoin('r', 'LATERAL (
                SELECT m.id, m.text, m.created_at, m.sender_id
                FROM chat_messages m
                WHERE m.room_id = r.id
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT 1
            )', 'lm', 'true')
            ->leftJoin('lm', 'users', 'lu', 'lu.id = lm.sender_id')
            ->orderBy('COALESCE(lm.created_at, r.created_at)', 'DESC');

        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(static function (array $row): ChatRoomDto {
            assert(is_string($row['id']));
            assert(is_string($row['user_id']));
            assert(is_string($row['user_email']));
            assert(is_string($row['created_at']));
            if ($row['last_message_id'] !== null) {
                assert(is_string($row['last_message_id']));
                assert(is_string($row['last_message_sender_id']));
                assert(is_string($row['last_message_text']));
                assert(is_string($row['last_message_created_at']));
            }
            if ($row['last_message_sender_name'] !== null) {
                assert(is_string($row['last_message_sender_name']));
            }

            return new ChatRoomDto(
                id: $row['id'],
                userId: $row['user_id'],
                userEmail: $row['user_email'],
                createdAt: (new \DateTimeImmutable($row['created_at']))->format('c'),
                lastMessage: $row['last_message_id'] !== null ? new ChatMessageDto(
                    id: $row['last_message_id'],
                    roomId: $row['id'],
                    senderId: $row['last_message_sender_id'],
                    senderName: $row['last_message_sender_name'] !== null ? $row['last_message_sender_name'] : 'Unknown',
                    isSupport: self::senderIsSupport($row['last_message_sender_roles']),
                    text: $row['last_message_text'],
                    createdAt: (new \DateTimeImmutable($row['last_message_created_at']))->format('c'),
                ) : null,
            );
        }, $rows);
    }

    private static function senderIsSupport(mixed $rolesJson): bool
    {
        if (is_string($rolesJson)) {
            $roles = json_decode($rolesJson, true);
        } else {
            $roles = is_array($rolesJson) ? $rolesJson : null;
        }

        return \is_array($roles) && \in_array('ROLE_ADMIN', $roles, true);
    }

    public function save(ChatRoom $room): void
    {
        $this->entityManager->persist($room);
    }
}
