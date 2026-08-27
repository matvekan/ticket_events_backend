<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueId;
use App\Domain\ValueObject\VenueName;

class Venue
{
    private string $id;
    private VenueName $name;
    private VenueAddress $address;
    private VenueCity $city;
    private ?float $latitude = null;
    private ?float $longitude = null;

    private function __construct(
        VenueId $id,
        VenueName $name,
        VenueAddress $address,
        VenueCity $city,
        ?float $latitude,
        ?float $longitude,
    ) {
        $this->id = $id->toString();
        $this->name = $name;
        $this->address = $address;
        $this->city = $city;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public static function create(
        VenueName $name,
        VenueAddress $address,
        VenueCity $city,
        IdGeneratorInterface $ids,
        ?float $latitude = null,
        ?float $longitude = null,
    ): self {
        return new self(new VenueId($ids->generate()), $name, $address, $city, $latitude, $longitude);
    }

    public function id(): VenueId
    {
        return new VenueId($this->id);
    }

    public function rawId(): string { return $this->id; }

    public function name(): VenueName
    {
        return $this->name;
    }

    public function address(): VenueAddress
    {
        return $this->address;
    }

    public function city(): VenueCity
    {
        return $this->city;
    }

    public function latitude(): ?float
    {
        return $this->latitude;
    }

    public function longitude(): ?float
    {
        return $this->longitude;
    }
}
