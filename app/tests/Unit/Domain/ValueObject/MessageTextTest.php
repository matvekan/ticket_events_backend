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
        // Arrange
        $raw = '  Hello support!  ';

        // Act
        $text = new MessageText($raw);

        // Assert
        self::assertSame('Hello support!', $text->toString());
    }

    public function testRejectsEmptyText(): void
    {
        // Arrange
        $raw = '';

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        new MessageText($raw);
    }

    public function testRejectsWhitespaceOnlyText(): void
    {
        // Arrange
        $raw = "   \n\t  ";

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        new MessageText($raw);
    }

    public function testAcceptsBoundaryMaxLength(): void
    {
        // Arrange
        $raw = str_repeat('a', MessageText::MAX_LENGTH);

        // Act
        $text = new MessageText($raw);

        // Assert
        self::assertSame(MessageText::MAX_LENGTH, mb_strlen($text->toString()));
    }

    public function testRejectsOverMaxLength(): void
    {
        // Arrange
        $raw = str_repeat('a', MessageText::MAX_LENGTH + 1);

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        new MessageText($raw);
    }
}
