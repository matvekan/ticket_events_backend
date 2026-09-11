<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class ChatMessageDto
{
    public function __construct(
        public string $id,
        public string $roomId,
        public string $senderId,
        public string $senderName,
        public bool $isSupport,
        public string $text,
        public string $createdAt,
    ) {
    }
}
