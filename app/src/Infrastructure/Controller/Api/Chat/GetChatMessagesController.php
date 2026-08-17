<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Chat;

use App\Application\Dto\Factory\ChatMessageDtoFactory;
use App\Application\Service\Chat\ChatService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/chat/rooms/{id}/messages', name: 'chat.messages', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
final class GetChatMessagesController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatMessageDtoFactory $messageFactory,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            $messages = $this->chatService->listMessages(
                Uuid::fromRfc4122($id),
                $this->security->getUser(),
            );
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException) {
            return new JsonResponse(['error' => 'You do not have access to this chat room.'], Response::HTTP_FORBIDDEN);
        } catch (\App\Domain\Exception\EntityNotFoundException) {
            return new JsonResponse(['error' => 'Chat room not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->messageFactory->fromMessageList($messages));
    }
}