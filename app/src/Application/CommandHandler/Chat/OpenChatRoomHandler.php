<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Chat;

use App\Application\Command\Chat\OpenChatRoomCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\ChatRoom;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class OpenChatRoomHandler implements CommandHandlerInterface
{
    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private UserRepositoryInterface $users,
        private TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(OpenChatRoomCommand $command): ChatRoom
    {
        $userId = new UserId($command->userId);
        return $this->transactionManager->transactional(function () use ($userId): ChatRoom {
            $user = $this->users->findById($userId);
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
}
