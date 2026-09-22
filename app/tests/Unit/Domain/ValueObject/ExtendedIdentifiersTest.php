<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\ChatMessageId;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\TicketId;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExtendedIdentifiersTest extends TestCase
{
    public function testEventIdAcceptsWellFormedUuidAndExposesStringRepresentation(): void
    {
        $raw = FixedIdGenerator::uuid(1);

        $id = new EventId($raw);

        self::assertSame($raw, $id->toString());
        self::assertSame($raw, (string) $id);
    }

    public function testOrderIdAcceptsWellFormedUuidAndSupportsEquality(): void
    {
        $raw = FixedIdGenerator::uuid(2);

        $first = new OrderId($raw);
        $second = new OrderId($raw);

        self::assertTrue($first->equals($second));
    }

    public function testPaymentIdAcceptsWellFormedUuid(): void
    {
        $id = new PaymentId(FixedIdGenerator::uuid(3));

        self::assertSame(FixedIdGenerator::uuid(3), $id->toString());
    }

    public function testTicketIdAcceptsWellFormedUuid(): void
    {
        $id = new TicketId(FixedIdGenerator::uuid(4));

        self::assertSame(FixedIdGenerator::uuid(4), $id->toString());
    }

    public function testSeatIdAcceptsWellFormedUuid(): void
    {
        $id = new SeatId(FixedIdGenerator::uuid(5));

        self::assertSame(FixedIdGenerator::uuid(5), $id->toString());
    }

    public function testEventSeatIdAcceptsWellFormedUuid(): void
    {
        $id = new EventSeatId(FixedIdGenerator::uuid(6));

        self::assertSame(FixedIdGenerator::uuid(6), $id->toString());
    }

    public function testChatMessageIdAcceptsWellFormedUuid(): void
    {
        $id = new ChatMessageId(FixedIdGenerator::uuid(7));

        self::assertSame(FixedIdGenerator::uuid(7), $id->toString());
    }

    public function testIdentifiersWithDifferentValuesAreNotEqual(): void
    {
        $first = new EventId(FixedIdGenerator::uuid(1));
        $second = new EventId(FixedIdGenerator::uuid(2));

        self::assertFalse($first->equals($second));
    }

    #[DataProvider('invalidUuids')]
    public function testEventIdRejectsMalformedUuidValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EventId($raw);
    }

    #[DataProvider('invalidUuids')]
    public function testOrderIdRejectsMalformedUuidValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OrderId($raw);
    }

    #[DataProvider('invalidUuids')]
    public function testPaymentIdRejectsMalformedUuidValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new PaymentId($raw);
    }

    public static function invalidUuids(): iterable
    {
        yield 'empty' => [''];
        yield 'not-a-uuid' => ['not-a-uuid'];
        yield 'missing dashes' => ['1111111111114111811111111111100001'];
        yield 'invalid hex' => ['zzzzzzzz-1111-4111-8111-000000000001'];
    }
}
