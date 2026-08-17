<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Dto\ChatRoomDto;
use App\Application\Dto\Factory\ChatRoomDtoFactory;
use App\Application\Service\Chat\ChatService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/chat/rooms', name: 'admin.chat.rooms', methods: ['GET'])]
final class SupportChatRoomsController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatRoomDtoFactory $roomFactory,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $rooms = $this->chatService->listRoomsForSupport();

        return new JsonResponse(array_map(
            fn (array $entry): ChatRoomDto => $this->roomFactory->fromRoom(
                $entry['room'],
                $entry['lastMessage'],
            ),
            $rooms,
        ));
    }
}