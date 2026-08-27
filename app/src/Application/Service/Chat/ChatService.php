<?php

declare(strict_types=1);

namespace App\Application\Service\Chat;

use App\Application\Dto\ChatMessageDto;
use App\Application\Exception\AccessDeniedException;
use App\Application\Transaction\TransactionManagerInterface;
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

    /**
     * Idempotently ensures a support room exists for the user.
     */
    public function openRoom(string $userId): void
    {
        $this->transactionManager->transactional(function () use ($userId): void {
            $user = $this->users->findById(new UserId($userId));
            if (!$user) {
                throw new EntityNotFoundException('User not found.');
            }

            if ($this->rooms->findByUserId($user->id()) === null) {
                $room = \App\Domain\Entity\ChatRoom::create($user->id(), $this->clock, $this->ids);
                $this->rooms->save($room);
            }
        });
    }

    public function sendMessage(string $roomId, string $senderId, string $text): ChatMessageDto
    {
        return $this->transactionManager->transactional(
            function () use ($roomId, $senderId, $text): ChatMessageDto {
                $room = $this->rooms->findById(new ChatRoomId($roomId));
                if (!$room) {
                    throw new EntityNotFoundException('Chat room not found.');
                }

                $sender = $this->users->findById(new UserId($senderId));
                if (!$sender) {
                    throw new EntityNotFoundException('User not found.');
                }

                if (!$this->accessPolicy->canParticipate($room, $sender)) {
                    throw new AccessDeniedException('You do not have access to this chat room.');
                }

                $message = \App\Domain\Entity\ChatMessage::create(
                    $room->id(),
                    $sender->id(),
                    $text,
                    $this->clock,
                    $this->ids,
                );
                $this->messages->save($message);

                return new ChatMessageDto(
                    id: $message->id()->toRfc4122(),
                    roomId: $message->roomId()->toRfc4122(),
                    senderId: $message->senderId()->toRfc4122(),
                    senderName: (string) $sender->name(),
                    isSupport: $this->accessPolicy->isSupport($sender),
                    text: $message->text(),
                    createdAt: $message->createdAt()->format('c'),
                );
            },
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
