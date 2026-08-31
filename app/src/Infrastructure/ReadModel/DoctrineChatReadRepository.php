<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\ChatMessageDto;
use App\Application\Dto\ChatRoomDto;
use App\Application\Port\ChatRoomReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineChatReadRepository implements ChatRoomReadRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findForSupport(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT r.id, r.user_id, u.email AS user_email, r.created_at,
                       lm.id   AS last_message_id,
                       lm.text AS last_message_text,
                       lm.created_at AS last_message_created_at,
                       lm.sender_id  AS last_message_sender_id,
                       lu.name AS last_message_sender_name,
                       lu.roles AS last_message_sender_roles
                FROM chat_rooms r
                JOIN users u ON u.id = r.user_id
                LEFT JOIN LATERAL (
                    SELECT m.id, m.text, m.created_at, m.sender_id
                    FROM chat_messages m
                    WHERE m.room_id = r.id
                    ORDER BY m.created_at DESC, m.id DESC
                    LIMIT 1
                ) lm ON true
                LEFT JOIN users lu ON lu.id = lm.sender_id
                ORDER BY COALESCE(lm.created_at, r.created_at) DESC
            SQL,
        );

        return array_map(static fn (array $r): ChatRoomDto => new ChatRoomDto(
            id: (string) $r['id'],
            userId: (string) $r['user_id'],
            userEmail: (string) $r['user_email'],
            createdAt: (new \DateTimeImmutable((string) $r['created_at']))->format('c'),
            lastMessage: $r['last_message_id'] !== null ? new ChatMessageDto(
                id: (string) $r['last_message_id'],
                roomId: (string) $r['id'],
                senderId: (string) $r['last_message_sender_id'],
                senderName: $r['last_message_sender_name'] !== null ? (string) $r['last_message_sender_name'] : 'Unknown',
                isSupport: self::senderIsSupport($r['last_message_sender_roles']),
                text: (string) $r['last_message_text'],
                createdAt: (new \DateTimeImmutable((string) $r['last_message_created_at']))->format('c'),
            ) : null,
        ), $rows);
    }

    /** @param mixed $rolesJson raw JSON column value */
    private static function senderIsSupport(mixed $rolesJson): bool
    {
        $roles = is_array($rolesJson) ? $rolesJson : json_decode((string) $rolesJson, true);

        return is_array($roles) && in_array('ROLE_ADMIN', $roles, true);
    }
}
