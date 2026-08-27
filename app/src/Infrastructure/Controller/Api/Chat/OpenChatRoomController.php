<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Chat;

use App\Application\Command\Chat\OpenChatRoomCommand;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\Chat\GetChatRoomQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chat/room', name: 'chat.room', methods: ['POST'])]
final class OpenChatRoomController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        /** @var \App\Infrastructure\Security\DomainUserAdapter $user */
        $user = $this->security->getUser();
        $userId = $user->id()->toRfc4122();

        // Command mutates, query reads back — commands stay void.
        $this->commandBus->dispatch(new OpenChatRoomCommand($userId));

        return new JsonResponse(
            $this->queryBus->dispatch(new GetChatRoomQuery($userId)),
            Response::HTTP_CREATED,
        );
    }
}
