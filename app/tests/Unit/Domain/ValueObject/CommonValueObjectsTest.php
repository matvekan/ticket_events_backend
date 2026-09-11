<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatSector;
use PHPUnit\Framework\TestCase;

final class CommonValueObjectsTest extends TestCase
{
    public function testSeatNumberRejectsZero(): void
    {
        // Arrange
        $number = 0;

        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        new SeatNumber($number);
    }

    public function testSeatNumberAcceptsPositive(): void
    {
        // Arrange
        $number = 1;

        // Act
        $vo = new SeatNumber($number);

        // Assert
        self::assertSame(1, $vo->toInt());
    }

    public function testSeatSectorRejectsEmpty(): void
    {
        // Arrange
        $sector = '';

        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        new SeatSector($sector);
    }

    public function testSeatSectorAcceptsBoundary(): void
    {
        // Arrange
        $raw = str_repeat('s', 50);

        // Act
        $vo = new SeatSector($raw);

        // Assert
        self::assertSame($raw, $vo->toString());
    }

    public function testSeatSectorRejectsTooLong(): void
    {
        // Arrange
        $raw = str_repeat('s', 51);

        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        new SeatSector($raw);
    }

    public function testEmailNormalizesToLowercase(): void
    {
        // Arrange
        $raw = '  User@Example.COM ';

        // Act
        $email = new Email($raw);

        // Assert
        self::assertSame('user@example.com', $email->toString());
    }

    public function testEmailRejectsInvalid(): void
    {
        // Arrange
        $raw = 'not-an-email';

        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        new Email($raw);
    }

    public function testPriceRejectsNegativeAmount(): void
    {
        // Arrange — negative amount
        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        Price::fromAmount(-1);
    }

    public function testPriceNormalizesCurrencyToUppercase(): void
    {
        // Arrange
        $amount = 100;

        // Act
        $price = Price::fromAmount($amount, 'usd');

        // Assert
        self::assertSame('USD', $price->currency());
    }

    public function testPriceRejectsInvalidCurrency(): void
    {
        // Arrange — invalid ISO code
        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        Price::fromAmount(100, 'US');
    }

    public function testEventTitleRejectsTooShort(): void
    {
        // Arrange
        $raw = 'ab';

        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        new EventTitle($raw);
    }

    public function testEventTitleAcceptsBoundaries(): void
    {
        // Arrange
        $min = str_repeat('a', 3);
        $max = str_repeat('a', 100);

        // Act
        $minVo = new EventTitle($min);
        $maxVo = new EventTitle($max);

        // Assert
        self::assertSame($min, $minVo->toString());
        self::assertSame($max, $maxVo->toString());
    }

    public function testEventDescriptionRejectsTooShort(): void
    {
        // Arrange
        $raw = str_repeat('a', 9);

        // Act + Assert
        $this->expectException(\InvalidArgumentException::class);
        new EventDescription($raw);
    }

    public function testEventDescriptionAcceptsBoundaries(): void
    {
        // Arrange
        $min = str_repeat('a', 10);
        $max = str_repeat('a', 5000);

        // Act
        $minVo = new EventDescription($min);
        $maxVo = new EventDescription($max);

        // Assert
        self::assertSame($min, $minVo->toString());
        self::assertSame($max, $maxVo->toString());
    }
}
