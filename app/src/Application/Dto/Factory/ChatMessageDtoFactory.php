<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\ChatMessageDto;
use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\User;

final class ChatMessageDtoFactory
{
    /** @param ChatMessage[] $messages @return ChatMessageDto[] */
    public function fromMessageList(array $messages, array $senders): array
    {
        return array_map(
            fn (ChatMessage $message): ChatMessageDto => $this->build(
                $message,
                $senders[$message->senderId()->toString()] ?? null,
            ),
            $messages,
        );
    }

    private function build(ChatMessage $message, ?User $sender): ChatMessageDto
    {
        return new ChatMessageDto(
            id: $message->id()->toRfc4122(),
            roomId: $message->roomId()->toRfc4122(),
            senderId: $message->senderId()->toRfc4122(),
            senderName: $sender !== null ? (string) $sender->name() : 'Unknown',
            isSupport: $sender !== null && in_array('ROLE_ADMIN', $sender->roles(), true),
            text: $message->text()->toString(),
            createdAt: $message->createdAt()->format('c'),
        );
    }
}
