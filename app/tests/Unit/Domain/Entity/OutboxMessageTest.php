<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\SeatsReservedEvent;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class OutboxMessageTest extends TestCase
{
    public function testCreateStoresDomainEventClassAndSerializedBody(): void
    {
        $message = $this->createReservedSeatsMessage();

        self::assertSame(SeatsReservedEvent::class, $message->messageClass());
        self::assertSame($this->serializedBody(), $message->body());
        self::assertFalse($message->isSent());
    }

    public function testCreateAssignsDeterministicIdentifierFromGenerator(): void
    {
        $ids = DomainFixture::ids(9, 5);
        $expected = $ids->generate();

        $message = $this->createMessageWithFreshIds(9);

        self::assertSame($expected, $message->id());
    }

    public function testMarkSentFlagsMessageAsSuccessfullyPublished(): void
    {
        $message = $this->createReservedSeatsMessage();

        $message->markSent(new \DateTimeImmutable('2026-06-01T13:00:00Z'));

        self::assertTrue($message->isSent());
    }

    public function testNewMessageIsNotSentBeforeMarkSentInvocation(): void
    {
        $message = $this->createReservedSeatsMessage();

        self::assertFalse($message->isSent());
    }

    public function testMarkFailedKeepsMessageInUnpublishedState(): void
    {
        $message = $this->createReservedSeatsMessage();

        $message->markFailed();

        self::assertFalse($message->isSent());
    }

    private function createReservedSeatsMessage(): OutboxMessage
    {
        return new OutboxMessage(
            SeatsReservedEvent::class,
            $this->serializedBody(),
            DomainFixture::clock(),
            DomainFixture::ids(),
        );
    }

    private function createMessageWithFreshIds(int $from): OutboxMessage
    {
        return new OutboxMessage(
            SeatsReservedEvent::class,
            $this->serializedBody(),
            DomainFixture::clock(),
            DomainFixture::ids($from, 5),
        );
    }

    private function serializedBody(): string
    {
        return base64_encode(serialize(new SeatsReservedEvent('order-id', 'user-id', ['seat-id'])));
    }
}
