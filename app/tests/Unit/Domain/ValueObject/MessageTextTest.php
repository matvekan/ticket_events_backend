<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\MessageText;
use PHPUnit\Framework\TestCase;

final class MessageTextTest extends TestCase
{
    public function testAcceptsNormalTextAndTrims(): void
    {
        $raw = '  Hello support!  ';

        $text = new MessageText($raw);

        self::assertSame('Hello support!', $text->toString());
    }

    public function testRejectsEmptyText(): void
    {
        $raw = '';

        $this->expectException(BusinessRuleViolationException::class);
        new MessageText($raw);
    }

    public function testRejectsWhitespaceOnlyText(): void
    {
        $raw = "   \n\t  ";

        $this->expectException(BusinessRuleViolationException::class);
        new MessageText($raw);
    }

    public function testAcceptsBoundaryMaxLength(): void
    {
        $raw = str_repeat('a', MessageText::MAX_LENGTH);

        $text = new MessageText($raw);

        self::assertSame(MessageText::MAX_LENGTH, mb_strlen($text->toString()));
    }

    public function testRejectsOverMaxLength(): void
    {
        $raw = str_repeat('a', MessageText::MAX_LENGTH + 1);

        $this->expectException(BusinessRuleViolationException::class);
        new MessageText($raw);
    }
}
