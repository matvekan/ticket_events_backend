<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Chat;

use App\Application\Dto\ChatMessageDto;
use App\Application\Dto\Factory\ChatMessageDtoFactory;
use App\Application\Exception\AccessDeniedException;
use App\Application\Query\Chat\GetChatMessagesQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ChatAccessPolicy;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetChatMessagesHandler implements QueryHandlerInterface
{
    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private ChatMessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private ChatMessageDtoFactory $factory,
        private ChatAccessPolicy $accessPolicy,
    ) {}

    /** @return ChatMessageDto[] */
    public function __invoke(GetChatMessagesQuery $query): array
    {
        $room = $this->rooms->findById(new ChatRoomId($query->roomId));
        if (!$room) {
            throw new EntityNotFoundException('Chat room not found.');
        }
        $viewer = $this->users->findById(new UserId($query->viewerId));
        if (!$viewer) {
            throw new EntityNotFoundException('User not found.');
        }
        if (!$this->accessPolicy->canParticipate($room, $viewer)) {
            throw new AccessDeniedException('You do not have access to this chat room.');
        }
        $messages = $this->messages->findByRoomId($room->id());
        return $this->factory->fromMessageList($messages);
    }
}
