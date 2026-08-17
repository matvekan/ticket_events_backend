<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Chat;

use App\Application\Dto\Factory\ChatRoomDtoFactory;
use App\Application\Service\Chat\ChatService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chat/room', name: 'chat.room', methods: ['POST'])]
final class OpenChatRoomController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatRoomDtoFactory $roomFactory,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $room = $this->chatService->openRoom($this->security->getUser()->id());

        return new JsonResponse(
            $this->roomFactory->fromRoom($room),
            Response::HTTP_CREATED,
        );
    }
}