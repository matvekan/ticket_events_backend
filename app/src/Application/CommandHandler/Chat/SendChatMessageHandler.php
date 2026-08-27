<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Chat;

use App\Application\Command\Chat\SendChatMessageCommand;
use App\Application\Command\CommandHandlerInterface;
use App\Application\Service\Chat\ChatService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Commands are fire-and-forget: consumers read messages back via
 * GetChatMessagesQuery (or receive them over WebSocket).
 */
#[AsMessageHandler]
final readonly class SendChatMessageHandler implements CommandHandlerInterface
{
    public function __construct(
        private ChatService $chatService,
    ) {
    }

    public function __invoke(SendChatMessageCommand $command): void
    {
        $this->chatService->sendMessage($command->roomId, $command->senderId, $command->text);
    }
}
