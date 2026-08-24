<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Chat;

use App\Application\Command\Chat\OpenChatRoomCommand;
use App\Application\Command\CommandBusInterface;
use App\Application\Dto\Factory\ChatRoomDtoFactory;
use App\Domain\Entity\ChatRoom;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/chat/room', name: 'chat.room', methods: ['POST'])]
final class OpenChatRoomController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly ChatRoomDtoFactory $roomFactory,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        $userId = method_exists($user, 'id') ? $user->id()->toString() : $user->getUserIdentifier();
        /** @var ChatRoom $room */
        $room = $this->commandBus->dispatch(new OpenChatRoomCommand($userId));

        return new JsonResponse(
            $this->roomFactory->fromRoom($room),
            Response::HTTP_CREATED,
        );
    }
}