<?php

declare(strict_types=1);

namespace App\Application\Service\Chat;

use App\Application\Dto\ChatMessageDto;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ChatAccessPolicy;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;

final readonly class ChatService
{
    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private ChatMessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transactionManager,
        private ChatAccessPolicy $accessPolicy,
        private ClockInterface $clock,
        private IdGeneratorInterface $ids,
    ) {
    }

    public function openRoom(string $userId): void
    {
        $this->transactionManager->transactional(function () use ($userId): void {
            $user = $this->users->findById(new UserId($userId));
            if (!$user) {
                throw new EntityNotFoundException('User not found.');
            }

            if ($this->rooms->findByUserId($user->id()) === null) {
                $room = ChatRoom::create($user->id(), $this->clock, $this->ids);
                $this->rooms->save($room);
            }
        });
    }

    public function createMessage(ChatRoom $room, User $sender, string $text): ChatMessageDto
    {
        return $this->transactionManager->transactional(function () use ($room, $sender, $text): ChatMessageDto {
            $message = ChatMessage::create(
                $room->id(),
                $sender->id(),
                $text,
                $this->clock,
                $this->ids,
            );
            $this->messages->save($message);

            return $this->toDto($message, $sender);
        });
    }

    private function toDto(ChatMessage $message, User $sender): ChatMessageDto
    {
        return new ChatMessageDto(
            id: $message->id()->toString(),
            roomId: $message->roomId()->toString(),
            senderId: $message->senderId()->toString(),
            senderName: (string) $sender->name(),
            isSupport: $this->accessPolicy->isSupport($sender),
            text: $message->text()->toString(),
            createdAt: $message->createdAt()->format('c'),
        );
    }

    public function canAccess(string $roomId, User $viewer): bool
    {
        $room = $this->rooms->findById(new ChatRoomId($roomId));
        if (!$room) {
            return false;
        }

        return $this->accessPolicy->canParticipate($room, $viewer);
    }
}
