<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\TicketCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TicketCodeTest extends TestCase
{
    public function testAcceptsValidCode(): void
    {
        $raw = 'TKT-ABC12345';

        $code = new TicketCode($raw);

        self::assertSame($raw, $code->toString());
        self::assertTrue($code->equals(new TicketCode($raw)));
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsInvalidCode(string $raw): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TicketCode($raw);
    }

    public static function invalidCodes(): iterable
    {
        yield 'lowercase prefix' => ['tkt-ABC12345'];
        yield 'too short' => ['TKT-ABC1234'];
        yield 'too long' => ['TKT-ABC123456'];
        yield 'lowercase body' => ['TKT-abc12345'];
        yield 'dash in body' => ['TKT-ABC-1234'];
        yield 'empty' => [''];
        yield 'no prefix' => ['ABC12345'];
    }
}
