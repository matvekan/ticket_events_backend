<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Chat\GetSupportChatRoomsQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/chat/rooms', name: 'admin.chat.rooms', methods: ['GET'])]
#[OA\Tag(name: 'Admin')]
#[OA\Get(
    path: '/api/admin/chat/rooms',
    summary: 'List support chat rooms (admin)',
    tags: ['Admin'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of support chat rooms', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ChatRoom'))),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
final class SupportChatRoomsController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->queryBus->dispatch(new GetSupportChatRoomsQuery()));
    }
}
