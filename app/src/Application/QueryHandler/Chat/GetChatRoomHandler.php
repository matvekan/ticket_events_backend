<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Chat;

use App\Application\Dto\ChatRoomDto;
use App\Application\Dto\Factory\ChatRoomDtoFactory;
use App\Application\Query\Chat\GetChatRoomQuery;
use App\Application\Query\QueryHandlerInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetChatRoomHandler implements QueryHandlerInterface
{
    public function __construct(
        private ChatRoomRepositoryInterface $rooms,
        private UserRepositoryInterface $users,
        private ChatRoomDtoFactory $factory,
    ) {
    }

    public function __invoke(GetChatRoomQuery $query): ChatRoomDto
    {
        $room = $this->rooms->findByUserId(new UserId($query->userId));
        if ($room === null) {
            throw new EntityNotFoundException('Chat room not found.');
        }

        $owner = $this->users->findById($room->userId());

        return $this->factory->fromRoom($room, $owner);
    }
}
