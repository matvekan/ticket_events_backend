<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Chat;

use App\Application\Query\Chat\GetChatMessagesQuery;
use App\Application\Query\QueryBusInterface;
use App\Domain\Exception\EntityNotFoundException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/chat/rooms/{id}/messages', name: 'chat.messages', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
final class GetChatMessagesController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            $viewerId = method_exists($user, 'id') ? $user->id()->toString() : $user->getUserIdentifier();

            return new JsonResponse(
                $this->queryBus->dispatch(new GetChatMessagesQuery($id, $viewerId)),
            );
        } catch (AccessDeniedException) {
            return new JsonResponse(['error' => 'You do not have access to this chat room.'], Response::HTTP_FORBIDDEN);
        } catch (EntityNotFoundException) {
            return new JsonResponse(['error' => 'Chat room not found.'], Response::HTTP_NOT_FOUND);
        }
    }
}
