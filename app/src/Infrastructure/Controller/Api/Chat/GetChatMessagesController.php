<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Chat;

use App\Application\Query\Chat\GetChatMessagesQuery;
use App\Application\Query\QueryBusInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Infrastructure\Controller\Api\Shared\RequiresDomainUserTrait;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/chat/rooms/{id}/messages', name: 'chat.messages', methods: ['GET'], requirements: ['id' => '[0-9a-f-]+'])]
#[OA\Tag(name: 'Chat')]
#[OA\Parameter(name: 'id', description: 'Chat room UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Get(
    path: '/api/chat/rooms/{id}/messages',
    summary: 'Get chat messages',
    tags: ['Chat'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of messages', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ChatMessage'))),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden - no access to this chat room'),
        new OA\Response(response: 404, description: 'Chat room not found'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class GetChatMessagesController
{
    use RequiresDomainUserTrait;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        try {
            $viewerId = $this->getDomainUser($this->security)->id()->toString();

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
