<?php

declare(strict_types=1);

namespace App\Application\Service\Chat;

use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final readonly class ChatService
{
    public const SUPPORT_ROLE = 'ROLE_ADMIN';

    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private ChatMessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function openRoom(Uuid $userId): ChatRoom
    {
        return $this->transactionManager->transactional(function () use ($userId): ChatRoom {
            $user = $this->users->findById(new UserId($userId->toRfc4122()));
            if (!$user) {
                throw new EntityNotFoundException('User not found.');
            }

            $room = $this->rooms->findByUserId($userId);
            if ($room === null) {
                $room = ChatRoom::create($user);
                $this->rooms->save($room);
            }

            return $room;
        });
    }

    public function sendMessage(Uuid $roomId, UserId $senderId, string $text): ChatMessage
    {
        $message = $this->transactionManager->transactional(function () use ($roomId, $senderId, $text): ChatMessage {
            $room = $this->rooms->findById(new ChatRoomId($roomId->toRfc4122()));
            if (!$room) {
                throw new EntityNotFoundException('Chat room not found.');
            }

            $sender = $this->users->findById($senderId);
            if (!$sender) {
                throw new EntityNotFoundException('User not found.');
            }

            $this->assertParticipant($room, $sender);

            $message = ChatMessage::create($room, $sender, $text);
            $this->messages->save($message);

            return $message;
        });

        return $message;
    }

    /** @return ChatMessage[] */
    public function listMessages(Uuid $roomId, User $viewer): array
    {
        $room = $this->rooms->findById($roomId);
        if (!$room) {
            throw new EntityNotFoundException('Chat room not found.');
        }

        $this->assertParticipant($room, $viewer);

        return $this->messages->findByRoomId($roomId);
    }

    public function canAccess(Uuid $roomId, User $viewer): bool
    {
        $room = $this->rooms->findById($roomId);
        if (!$room) {
            return false;
        }

        try {
            $this->assertParticipant($room, $viewer);

            return true;
        } catch (AccessDeniedException) {
            return false;
        }
    }

    /**
     * @return array<int, array{room: ChatRoom, lastMessage: ?ChatMessage}>
     */
    public function listRoomsForSupport(): array
    {
        $rooms = $this->rooms->findAll();
        $latestByRoomId = $this->messages->findLatestForRooms($rooms);

        $lastCreatedAt = static fn (ChatRoom $room): \DateTimeImmutable => (
            $latestByRoomId[$room->id()->toRfc4122()] ?? null
        )?->createdAt() ?? $room->createdAt();

        usort($rooms, static fn (ChatRoom $a, ChatRoom $b): int => $lastCreatedAt($b) <=> $lastCreatedAt($a));

        return array_map(
            static fn (ChatRoom $room): array => [
                'room' => $room,
                'lastMessage' => $latestByRoomId[$room->id()->toRfc4122()] ?? null,
            ],
            $rooms,
        );
    }

    public function isSupport(User $user): bool
    {
        return in_array(self::SUPPORT_ROLE, $user->getRoles(), true);
    }

    private function assertParticipant(ChatRoom $room, User $user): void
    {
        $isOwner = $room->user()->id()->equals($user->id());

        if (!$isOwner && !$this->isSupport($user)) {
            throw new AccessDeniedException('You do not have access to this chat room.');
        }
    }
}