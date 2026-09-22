<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VenueValueObjectsTest extends TestCase
{
    public function testVenueNameAcceptsMinimumBoundaryOfTwoCharacters(): void
    {
        $name = new VenueName('AB');

        self::assertSame('AB', $name->toString());
    }

    public function testVenueNameAcceptsMaximumBoundaryOf255Characters(): void
    {
        $raw = str_repeat('a', 255);

        $name = new VenueName($raw);

        self::assertSame($raw, $name->toString());
    }

    public function testVenueNameTrimsSurroundingWhitespaceBeforeValidation(): void
    {
        $name = new VenueName('  Main Hall  ');

        self::assertSame('Main Hall', $name->toString());
    }

    #[DataProvider('invalidVenueNames')]
    public function testVenueNameRejectsTooShortTooLongOrEmptyValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VenueName($raw);
    }

    public static function invalidVenueNames(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'single character' => ['A'];
        yield 'too long' => [str_repeat('a', 256)];
    }

    public function testVenueAddressAcceptsMinimumBoundaryOfFiveCharacters(): void
    {
        $address = new VenueAddress('12345');

        self::assertSame('12345', $address->toString());
    }

    public function testVenueAddressAcceptsMaximumBoundaryOf255Characters(): void
    {
        $raw = str_repeat('a', 255);

        $address = new VenueAddress($raw);

        self::assertSame($raw, $address->toString());
    }

    #[DataProvider('invalidVenueAddresses')]
    public function testVenueAddressRejectsTooShortTooLongOrEmptyValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VenueAddress($raw);
    }

    public static function invalidVenueAddresses(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['1234'];
        yield 'whitespace only' => ['     '];
        yield 'too long' => [str_repeat('a', 256)];
    }

    public function testVenueCityAcceptsMinimumBoundaryOfTwoCharacters(): void
    {
        $city = new VenueCity('MS');

        self::assertSame('MS', $city->toString());
    }

    public function testVenueCityAcceptsMaximumBoundaryOf100Characters(): void
    {
        $raw = str_repeat('a', 100);

        $city = new VenueCity($raw);

        self::assertSame($raw, $city->toString());
    }

    #[DataProvider('invalidVenueCities')]
    public function testVenueCityRejectsTooShortTooLongOrEmptyValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VenueCity($raw);
    }

    public static function invalidVenueCities(): iterable
    {
        yield 'empty' => [''];
        yield 'single character' => ['M'];
        yield 'whitespace only' => ['  '];
        yield 'too long' => [str_repeat('a', 101)];
    }
}
