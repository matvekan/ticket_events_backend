<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Chat\GetSupportChatRoomsQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/chat/rooms', name: 'admin.chat.rooms', methods: ['GET'])]
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
