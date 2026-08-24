<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Chat;

use App\Application\Dto\ChatMessageDto;
use App\Application\Dto\Factory\ChatMessageDtoFactory;
use App\Application\Query\Chat\GetChatMessagesQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetChatMessagesHandler implements QueryHandlerInterface
{
    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private ChatMessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private ChatMessageDtoFactory $factory,
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
        $isOwner = $room->user()->id()->equals($viewer->id());
        $isSupport = in_array('ROLE_ADMIN', $viewer->roles(), true);
        if (!$isOwner && !$isSupport) {
            throw new AccessDeniedException('You do not have access to this chat room.');
        }
        $messages = $this->messages->findByRoomId(new ChatRoomId($query->roomId));
        return $this->factory->fromMessageList($messages);
    }
}
