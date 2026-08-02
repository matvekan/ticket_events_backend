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
    private Collection $seats;

    private function __construct(VenueName $name, VenueAddress $address, VenueCity $city)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->address = $address;
        $this->city = $city;
        $this->seats = new ArrayCollection();
    }

    public static function create(VenueName $name, VenueAddress $address, VenueCity $city): self
    {
        return new self($name, $address, $city);
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

    /** @return Collection<int, Seat> */
    public function seats(): Collection
    {
        return $this->seats;
    }

    public function addSeat(Seat $seat): void
    {
        if (!$this->seats->contains($seat)) {
            $this->seats->add($seat);
            $seat->assignToVenue($this);
        }
    }
}
