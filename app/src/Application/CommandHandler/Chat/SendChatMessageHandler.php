<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Chat;

use App\Application\Command\Chat\SendChatMessageCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\EntityNotFoundException;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ChatAccessPolicy;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
final readonly class SendChatMessageHandler implements CommandHandlerInterface
{
    public function __construct(
        private ChatService $chatService,
        private ChatRoomRepositoryInterface $rooms,
        private UserRepositoryInterface $users,
        private ChatAccessPolicy $accessPolicy,
    ) {
    }

    public function __invoke(SendChatMessageCommand $command): void
    {
        $room = $this->rooms->findById(new ChatRoomId($command->roomId));
        if (!$room) {
            throw new EntityNotFoundException('Chat room not found.');
        }

        $sender = $this->users->findById(new UserId($command->senderId));
        if (!$sender) {
            throw new EntityNotFoundException('User not found.');
        }

        if (!$this->accessPolicy->canParticipate($room, $sender)) {
            throw new \App\Application\Exception\AccessDeniedException('You do not have access to this chat room.');
        }

        $this->chatService->createMessage($room, $sender, $command->text);
    }
}
