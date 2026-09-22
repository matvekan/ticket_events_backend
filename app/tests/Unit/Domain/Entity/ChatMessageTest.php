<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\ChatRoom;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class ChatMessageTest extends TestCase
{
    public function testCreateInitializesMessageWithRoomSenderTextAndTimestamp(): void
    {
        $clock = DomainFixture::clock('2026-06-01T12:00:00Z');
        $ids = DomainFixture::ids();
        $room = $this->createChatRoom($ids, $clock);

        $message = ChatMessage::create($room->id(), $room->userId(), 'Hello support!', $clock, $ids);

        self::assertTrue($message->roomId()->equals($room->id()));
        self::assertTrue($message->senderId()->equals($room->userId()));
        self::assertSame('Hello support!', $message->text()->toString());
        self::assertSame('2026-06-01T12:00:00+00:00', $message->createdAt()->format(\DateTimeInterface::ATOM));
    }

    public function testCreateTrimsMessageTextAndPreservesNormalizedValue(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $room = $this->createChatRoom($ids, $clock);

        $message = ChatMessage::create($room->id(), $room->userId(), '  Hello support!  ', $clock, $ids);

        self::assertSame('Hello support!', $message->text()->toString());
    }

    public function testCreateThrowsBusinessRuleViolationWhenMessageTextIsEmpty(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $room = $this->createChatRoom($ids, $clock);

        $this->expectException(BusinessRuleViolationException::class);

        ChatMessage::create($room->id(), $room->userId(), '   ', $clock, $ids);
    }

    public function testCreateThrowsBusinessRuleViolationWhenMessageTextExceedsMaximumLength(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $room = $this->createChatRoom($ids, $clock);

        $this->expectException(BusinessRuleViolationException::class);

        ChatMessage::create($room->id(), $room->userId(), str_repeat('a', 2001), $clock, $ids);
    }

    public function testCreateAssignsDeterministicIdentifierFromGenerator(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids(10, 10);
        $room = $this->createChatRoom(DomainFixture::ids(1, 10), $clock);
        $expected = $ids->generate();
        $idsForMessage = DomainFixture::ids(10, 10);

        $message = ChatMessage::create($room->id(), $room->userId(), 'Hi', $clock, $idsForMessage);

        self::assertSame($expected, $message->rawId());
    }

    private function createChatRoom(mixed $ids, mixed $clock): ChatRoom
    {
        $user = DomainFixture::user($ids);

        return ChatRoom::create($user->id(), $clock, $ids);
    }
}
