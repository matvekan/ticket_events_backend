<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Chat;

use App\Application\Dto\ChatRoomDto;
use App\Application\Port\ChatRoomReadRepositoryInterface;
use App\Application\Query\Chat\GetSupportChatRoomsQuery;
use App\Application\Query\QueryHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSupportChatRoomsHandler implements QueryHandlerInterface
{
    public function __construct(
        private ChatRoomReadRepositoryInterface $rooms,
    ) {
    }

    /** @return ChatRoomDto[] */
    public function __invoke(GetSupportChatRoomsQuery $query): array
    {
        return $this->rooms->findForSupport();
    }
}
