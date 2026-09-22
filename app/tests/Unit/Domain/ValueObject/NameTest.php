<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Name;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NameTest extends TestCase
{
    public function testAcceptsMinimumBoundaryOfTwoCharacters(): void
    {
        $name = new Name('AB');

        self::assertSame('AB', $name->toString());
        self::assertTrue($name->equals(new Name('AB')));
    }

    public function testAcceptsMaximumBoundaryOfFiftyCharacters(): void
    {
        $raw = str_repeat('a', 50);

        $name = new Name($raw);

        self::assertSame($raw, $name->toString());
    }

    public function testTrimsSurroundingWhitespaceBeforeValidation(): void
    {
        $name = new Name('  Test User  ');

        self::assertSame('Test User', $name->toString());
    }

    public function testFromStringAndFromValueAliasesCreateIdenticalInstances(): void
    {
        $first = Name::fromString('Test User');
        $second = Name::fromValue('Test User');

        self::assertTrue($first->equals($second));
    }

    public function testEqualityReturnsFalseForDifferentValues(): void
    {
        $first = new Name('Alice');
        $second = new Name('Bob');

        self::assertFalse($first->equals($second));
    }

    #[DataProvider('invalidNames')]
    public function testRejectsTooShortTooLongOrEmptyValues(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Name($raw);
    }

    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'single character' => ['A'];
        yield 'too long' => [str_repeat('a', 51)];
    }
}
