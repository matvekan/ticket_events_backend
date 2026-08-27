<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\ChatMessageDto;
use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;

final class ChatMessageDtoFactory
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function fromMessage(ChatMessage $message): ChatMessageDto
    {
        $sender = $this->users->findById($message->senderId());

        return $this->build($message, $sender);
    }

    /** @param ChatMessage[] $messages @return ChatMessageDto[] */
    public function fromMessageList(array $messages): array
    {
        // Batch-resolve senders to avoid N+1 lookups.
        $senders = $this->users->findByIds(array_map(
            static fn (ChatMessage $message): UserId => $message->senderId(),
            $messages,
        ));

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
            text: $message->text(),
            createdAt: $message->createdAt()->format('c'),
        );
    }
}
