<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Venue
{
    private Uuid $id;
    private VenueName $name;
    private VenueAddress $address;
    private VenueCity $city;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private Collection $seats;

    private function __construct(VenueName $name, VenueAddress $address, VenueCity $city, ?float $latitude, ?float $longitude)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->address = $address;
        $this->city = $city;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->seats = new ArrayCollection();
    }

    public static function create(VenueName $name, VenueAddress $address, VenueCity $city, ?float $latitude = null, ?float $longitude = null): self
    {
        return new self($name, $address, $city, $latitude, $longitude);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

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
