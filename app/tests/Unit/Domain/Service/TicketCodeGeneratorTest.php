<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Service\Order\TicketCodeGenerator;
use App\Domain\ValueObject\TicketCode;
use PHPUnit\Framework\TestCase;

final class TicketCodeGeneratorTest extends TestCase
{
    public function testGenerateProducesValidTicketCodeMatchingExpectedPattern(): void
    {
        $generator = new TicketCodeGenerator();

        $code = $generator->generate();

        self::assertInstanceOf(TicketCode::class, $code);
        self::assertMatchesRegularExpression('/^TKT-[A-Z0-9]{8}$/', $code->toString());
    }

    public function testGenerateProducesUniqueCodesAcrossConsecutiveInvocations(): void
    {
        $generator = new TicketCodeGenerator();

        $first = $generator->generate()->toString();
        $second = $generator->generate()->toString();

        self::assertNotSame($first, $second);
    }

    public function testGenerateProducesUppercaseAlphanumericBody(): void
    {
        $generator = new TicketCodeGenerator();

        $code = $generator->generate()->toString();
        $body = substr($code, 4);

        self::assertSame(strtoupper($body), $body);
        self::assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $body);
    }
}
