<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Chat;

use App\Application\Command\Chat\SendChatMessageCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\ChatMessage;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler]
final readonly class SendChatMessageHandler implements CommandHandlerInterface
{
    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private ChatMessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(SendChatMessageCommand $command): ChatMessage
    {
        return $this->transactionManager->transactional(function () use ($command): ChatMessage {
            $roomId = new ChatRoomId($command->roomId);
            $senderId = new UserId($command->senderId);
            $room = $this->rooms->findById($roomId);
            if (!$room) {
                throw new EntityNotFoundException('Chat room not found.');
            }
            $sender = $this->users->findById($senderId);
            if (!$sender) {
                throw new EntityNotFoundException('User not found.');
            }
            $isOwner = $room->user()->id()->equals($sender->id());
            $isSupport = in_array('ROLE_ADMIN', $sender->roles(), true);
            if (!$isOwner && !$isSupport) {
                throw new AccessDeniedException('You do not have access to this chat room.');
            }
            $message = ChatMessage::create($room, $sender, $command->text);
            $this->messages->save($message);
            return $message;
        });
    }
}
