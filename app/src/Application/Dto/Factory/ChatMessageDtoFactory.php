<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\ChatMessageDto;
use App\Domain\Entity\ChatMessage;

final class ChatMessageDtoFactory
{
    public function fromMessage(ChatMessage $message): ChatMessageDto
    {
        $sender = $message->sender();

        return new ChatMessageDto(
            id: $message->id()->toRfc4122(),
            roomId: $message->room()->id()->toRfc4122(),
            senderId: $sender->id()->toRfc4122(),
            senderName: (string) $sender->name(),
            isSupport: in_array('ROLE_ADMIN', $sender->getRoles(), true),
            text: $message->text(),
            createdAt: $message->createdAt()->format('c'),
        );
    }

    /** @param ChatMessage[] $messages @return ChatMessageDto[] */
    public function fromMessageList(array $messages): array
    {
        return array_map(fn (ChatMessage $message): ChatMessageDto => $this->fromMessage($message), $messages);
    }
}