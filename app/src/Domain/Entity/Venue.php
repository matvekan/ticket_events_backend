<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'venues')]
class Venue extends AbstractEntity
{
    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $address;

    #[ORM\Column(length: 255)]
    private string $city;

    #[ORM\OneToMany(targetEntity: Seat::class, mappedBy: 'venue', cascade: ['persist'])]
    private Collection $seats;

    public function __construct(string $name, string $address, string $city)
    {
        $this->name = $name;
        $this->address = $address;
        $this->city = $city;
        $this->seats = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    /** @return Collection<int, Seat> */
    public function getSeats(): Collection
    {
        return $this->seats;
    }

    public function addSeat(Seat $seat): void
    {
        if (!$this->seats->contains($seat)) {
            $this->seats->add($seat);
            $seat->setVenue($this);
        }
    }
}
