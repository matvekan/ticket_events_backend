<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\ChatRoomDto;
use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\ChatRoom;

final class ChatRoomDtoFactory
{
    public function __construct(
        private readonly ChatMessageDtoFactory $messageFactory,
    ) {
    }

    public function fromRoom(ChatRoom $room, ?ChatMessage $lastMessage = null): ChatRoomDto
    {
        return new ChatRoomDto(
            id: $room->id()->toRfc4122(),
            userId: $room->user()->id()->toRfc4122(),
            userEmail: (string) $room->user()->email(),
            createdAt: $room->createdAt()->format('c'),
            lastMessage: $lastMessage !== null
                ? $this->messageFactory->fromMessage($lastMessage)
                : null,
        );
    }

    /**
     * @param ChatRoom[] $rooms
     * @param array<string, ChatMessage> $latestByRoomId
     * @return ChatRoomDto[]
     */
    public function fromRoomList(array $rooms, array $latestByRoomId = []): array
    {
        return array_map(
            fn (ChatRoom $room): ChatRoomDto => $this->fromRoom($room, $latestByRoomId[$room->id()->toRfc4122()] ?? null),
            $rooms,
        );
    }
}