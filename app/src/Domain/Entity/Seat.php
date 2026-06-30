<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\SeatType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'seats')]
class Seat extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Venue::class, inversedBy: 'seats')]
    #[ORM\JoinColumn(nullable: false)]
    private Venue $venue;

    #[ORM\Column(length: 10)]
    private string $row;

    #[ORM\Column(type: 'integer')]
    private int $number;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(type: 'string', enumType: SeatType::class, length: 20)]
    private SeatType $type;

    public function __construct(Venue $venue, string $row, int $number, SeatType $type, ?string $sector = null)
    {
        $this->venue = $venue;
        $this->row = $row;
        $this->number = $number;
        $this->type = $type;
        $this->sector = $sector;
    }

    public function getVenue(): Venue
    {
        return $this->venue;
    }

    public function setVenue(Venue $venue): void
    {
        $this->venue = $venue;
    }

    public function getRow(): string
    {
        return $this->row;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function getType(): SeatType
    {
        return $this->type;
    }
}
