<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class ChatRoomDto
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $userEmail,
        public string $createdAt,
        public ?ChatMessageDto $lastMessage,
    ) {
    }
}
