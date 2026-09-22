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
        $number = 0;

        $this->expectException(\InvalidArgumentException::class);
        new SeatNumber($number);
    }

    public function testSeatNumberAcceptsPositive(): void
    {
        $number = 1;

        $vo = new SeatNumber($number);

        self::assertSame(1, $vo->toInt());
    }

    public function testSeatSectorRejectsEmpty(): void
    {
        $sector = '';

        $this->expectException(\InvalidArgumentException::class);
        new SeatSector($sector);
    }

    public function testSeatSectorAcceptsBoundary(): void
    {
        $raw = str_repeat('s', 50);

        $vo = new SeatSector($raw);

        self::assertSame($raw, $vo->toString());
    }

    public function testSeatSectorRejectsTooLong(): void
    {
        $raw = str_repeat('s', 51);

        $this->expectException(\InvalidArgumentException::class);
        new SeatSector($raw);
    }

    public function testEmailNormalizesToLowercase(): void
    {
        $raw = '  User@Example.COM ';

        $email = new Email($raw);

        self::assertSame('user@example.com', $email->toString());
    }

    public function testEmailRejectsInvalid(): void
    {
        $raw = 'not-an-email';

        $this->expectException(\InvalidArgumentException::class);
        new Email($raw);
    }

    public function testPriceRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Price::fromAmount(-1);
    }

    public function testPriceNormalizesCurrencyToUppercase(): void
    {
        $amount = 100;
        $price = Price::fromAmount($amount, 'usd');

        self::assertSame('USD', $price->currency());
    }

    public function testPriceRejectsInvalidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Price::fromAmount(100, 'US');
    }

    public function testEventTitleRejectsTooShort(): void
    {
        $raw = 'ab';
        $this->expectException(\InvalidArgumentException::class);
        new EventTitle($raw);
    }

    public function testEventTitleAcceptsBoundaries(): void
    {
        $min = str_repeat('a', 3);
        $max = str_repeat('a', 100);

        $minVo = new EventTitle($min);
        $maxVo = new EventTitle($max);

        self::assertSame($min, $minVo->toString());
        self::assertSame($max, $maxVo->toString());
    }

    public function testEventDescriptionRejectsTooShort(): void
    {
        $raw = str_repeat('a', 9);

        $this->expectException(\InvalidArgumentException::class);
        new EventDescription($raw);
    }

    public function testEventDescriptionAcceptsBoundaries(): void
    {
        $min = str_repeat('a', 10);
        $max = str_repeat('a', 5000);

        $minVo = new EventDescription($min);
        $maxVo = new EventDescription($max);

        self::assertSame($min, $minVo->toString());
        self::assertSame($max, $maxVo->toString());
    }
}
