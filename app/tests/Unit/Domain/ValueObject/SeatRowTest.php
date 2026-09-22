<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\SeatRow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SeatRowTest extends TestCase
{
    public function testAcceptsSingleCharacterRow(): void
    {
        $raw = 'A';

        $row = new SeatRow($raw);

        self::assertSame('A', $row->toString());
    }

    public function testAcceptsMaxLengthRow(): void
    {
        $raw = str_repeat('A', 10);

        $row = new SeatRow($raw);

        self::assertSame($raw, $row->toString());
    }

    #[DataProvider('invalidRows')]
    public function testRejectsInvalidRow(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SeatRow($raw);
    }

    public static function invalidRows(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'too long (11)' => [str_repeat('A', 11)];
        yield 'dash not allowed' => ['A-1'];
        yield 'space inside' => ['A 1'];
        yield 'underscore' => ['ROW_1'];
    }
}
