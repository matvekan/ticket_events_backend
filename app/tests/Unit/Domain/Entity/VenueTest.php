<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Venue;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;

final class VenueTest extends TestCase
{
    public function testCreateInitializesVenueWithProvidedNameAddressAndCity(): void
    {
        $venue = DomainFixture::venue(DomainFixture::ids());

        self::assertSame('Main Hall', $venue->name()->toString());
        self::assertSame('123 Test Street', $venue->address()->toString());
        self::assertSame('Moscow', $venue->city()->toString());
    }

    public function testCreateAssignsDeterministicIdentifierFromGenerator(): void
    {
        $ids = DomainFixture::ids(7, 5);
        $expected = $ids->generate();

        $venue = $this->createVenueWithFreshIds(7);

        self::assertSame($expected, $venue->rawId());
        self::assertSame($expected, $venue->id()->toString());
    }

    public function testCreateStoresOptionalGeoCoordinatesWhenProvided(): void
    {
        $venue = $this->createVenueWithCoordinates(55.75, 37.61);

        self::assertSame(55.75, $venue->latitude());
        self::assertSame(37.61, $venue->longitude());
    }

    public function testCreateLeavesGeoCoordinatesEmptyByDefault(): void
    {
        $venue = DomainFixture::venue(DomainFixture::ids());

        self::assertNull($venue->latitude());
        self::assertNull($venue->longitude());
    }

    private function createVenueWithFreshIds(int $from): Venue
    {
        return DomainFixture::venue(DomainFixture::ids($from, 5));
    }

    private function createVenueWithCoordinates(float $latitude, float $longitude): Venue
    {
        return Venue::create(
            new VenueName('Main Hall'),
            new VenueAddress('123 Test Street'),
            new VenueCity('Moscow'),
            DomainFixture::ids(),
            $latitude,
            $longitude,
        );
    }
}
