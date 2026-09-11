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
        // Arrange
        $raw = 'A';

        // Act
        $row = new SeatRow($raw);

        // Assert
        self::assertSame('A', $row->toString());
    }

    public function testAcceptsMaxLengthRow(): void
    {
        // Arrange
        $raw = str_repeat('A', 10);

        // Act
        $row = new SeatRow($raw);

        // Assert
        self::assertSame($raw, $row->toString());
    }

    #[DataProvider('invalidRows')]
    public function testRejectsInvalidRow(string $raw): void
    {
        // Arrange — invalid row from provider
        // Act + Assert
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
