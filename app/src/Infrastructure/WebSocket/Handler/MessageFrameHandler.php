<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket\Handler;

use Amp\Websocket\WebsocketClient;
use App\Application\Service\Chat\ChatService;
use App\Domain\Entity\User;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ChatAccessPolicy;
use App\Domain\ValueObject\ChatRoomId;
use App\Infrastructure\WebSocket\ChatSubscriptions;
use App\Infrastructure\WebSocket\Dto\MessageFrame;
use JsonException;
use Psr\Log\LoggerInterface;
use Throwable;

final class MessageFrameHandler
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatRoomRepositoryInterface $rooms,
        private readonly UserRepositoryInterface $users,
        private readonly ChatAccessPolicy $accessPolicy,
        private readonly ChatSubscriptions $subscriptions,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(WebsocketClient $client, User $user, MessageFrame $frame): void
    {
        $room = $this->rooms->findById(new ChatRoomId($frame->roomId()));
        if (!$room) {
            $this->sendError($client, 'Chat room not found.');

            return;
        }

        $sender = $this->users->findById($user->id());
        if (!$sender) {
            $this->sendError($client, 'User not found.');

            return;
        }

        if (!$this->accessPolicy->canParticipate($room, $sender)) {
            $this->sendError($client, 'You do not have access to this chat room.');

            return;
        }

        try {
            $message = $this->chatService->createMessage($room, $sender, $frame->text());
        } catch (Throwable $exception) {
            $this->logger->warning(sprintf('Chat message rejected for %s: %s', $user->id()->toString(), $exception->getMessage()));
            $this->sendError($client, 'Message rejected.');

            return;
        }

        try {
            $payload = json_encode([
                'type' => 'message',
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        $this->subscriptions->broadcast($frame->roomId(), $payload);
    }

    private function sendError(WebsocketClient $client, string $message): void
    {
        try {
            $payload = json_encode(['type' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        $client->sendText($payload);
    }
}
