<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Chat;

use App\Application\Command\Chat\OpenChatRoomCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Service\Chat\ChatService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
final readonly class OpenChatRoomHandler implements CommandHandlerInterface
{
    public function __construct(
        private ChatService $chatService,
    ) {
    }

    public function __invoke(OpenChatRoomCommand $command): void
    {
        $this->chatService->openRoom($command->userId);
    }
}
