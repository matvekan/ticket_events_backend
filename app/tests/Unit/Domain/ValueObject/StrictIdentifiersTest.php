<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\VenueId;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StrictIdentifiersTest extends TestCase
{
    public function testUserIdAcceptsWellFormedUuidValue(): void
    {
        $id = new UserId(FixedIdGenerator::uuid(1));

        self::assertSame(FixedIdGenerator::uuid(1), $id->toString());
    }

    #[DataProvider('invalidUuids')]
    public function testUserIdRejectsMalformedUuidValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UserId($raw);
    }

    public function testVenueIdAcceptsWellFormedUuidValue(): void
    {
        $id = new VenueId(FixedIdGenerator::uuid(2));

        self::assertSame(FixedIdGenerator::uuid(2), $id->toString());
    }

    #[DataProvider('invalidUuids')]
    public function testVenueIdRejectsMalformedUuidValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VenueId($raw);
    }

    public function testChatRoomIdAcceptsWellFormedUuidValue(): void
    {
        $id = new ChatRoomId(FixedIdGenerator::uuid(3));

        self::assertSame(FixedIdGenerator::uuid(3), $id->toString());
    }

    #[DataProvider('invalidUuids')]
    public function testChatRoomIdRejectsMalformedUuidValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ChatRoomId($raw);
    }

    public function testIdentifiersWithSameValueAreConsideredEqual(): void
    {
        $first = new UserId(FixedIdGenerator::uuid(4));
        $second = new UserId(FixedIdGenerator::uuid(4));

        self::assertTrue($first->equals($second));
    }

    public function testIdentifiersWithDifferentValuesAreNotEqual(): void
    {
        $first = new UserId(FixedIdGenerator::uuid(4));
        $second = new UserId(FixedIdGenerator::uuid(5));

        self::assertFalse($first->equals($second));
    }

    public static function invalidUuids(): iterable
    {
        yield 'empty' => [''];
        yield 'plain string' => ['not-a-uuid'];
        yield 'missing dashes' => ['1111111111114111811111111111100001'];
        yield 'too short' => ['11111111-1111-4111-8111-0001'];
        yield 'invalid hex character' => ['zzzzzzzz-1111-4111-8111-000000000001'];
    }
}
