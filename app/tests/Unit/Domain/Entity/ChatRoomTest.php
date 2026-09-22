<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\ChatMessage;
use App\Domain\Entity\ChatRoom;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class ChatRoomTest extends TestCase
{
    public function testCreateInitializesSupportRoomForProvidedUser(): void
    {
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);

        $room = ChatRoom::create($user->id(), DomainFixture::clock(), $ids);

        self::assertTrue($room->userId()->equals($user->id()));
        self::assertNotEmpty($room->rawId());
    }

    public function testCreateAssignsDeterministicIdentifierFromGenerator(): void
    {
        $ids = DomainFixture::ids(3, 5);
        $user = DomainFixture::user($ids);
        $expected = \App\Tests\Unit\Domain\Support\FixedIdGenerator::uuid(4);

        $room = ChatRoom::create($user->id(), DomainFixture::clock(), $ids);

        self::assertSame($expected, $room->rawId());
        self::assertSame($expected, $room->id()->toString());
    }

    public function testCreateStampsRoomCreationTimeFromProvidedClock(): void
    {
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $clock = DomainFixture::clock('2026-06-01T12:00:00Z');

        $room = ChatRoom::create($user->id(), $clock, $ids);

        self::assertSame('2026-06-01T12:00:00+00:00', $room->createdAt()->format(\DateTimeInterface::ATOM));
    }

    public function testCreateChatMessageBindsTextToRoomAndSender(): void
    {
        $ids = DomainFixture::ids();
        $user = DomainFixture::user($ids);
        $clock = DomainFixture::clock();
        $room = ChatRoom::create($user->id(), $clock, $ids);

        $message = ChatMessage::create($room->id(), $user->id(), 'Hello support!', $clock, $ids);

        self::assertTrue($message->roomId()->equals($room->id()));
        self::assertTrue($message->senderId()->equals($user->id()));
        self::assertSame('Hello support!', $message->text()->toString());
    }
}
