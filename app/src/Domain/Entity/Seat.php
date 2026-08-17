<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use Symfony\Component\Uid\Uuid;

class Seat
{
    private Uuid $id;
    private Venue $venue;
    private SeatRow $row;
    private SeatNumber $number;
    private ?SeatSector $sector = null;
    private SeatType $type;

    private function __construct(
        Venue $venue,
        SeatRow $row,
        SeatNumber $number,
        SeatType $type,
        ?SeatSector $sector = null,
    ) {
        $this->id = Uuid::v7();
        $this->venue = $venue;
        $this->row = $row;
        $this->number = $number;
        $this->type = $type;
        $this->sector = $sector;
    }

    public static function create(
        Venue $venue,
        SeatRow $row,
        SeatNumber $number,
        SeatType $type,
        ?SeatSector $sector = null,
    ): self {
        return new self($venue, $row, $number, $type, $sector);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function venue(): Venue
    {
        return $this->venue;
    }

    public function row(): SeatRow
    {
        return $this->row;
    }

    public function number(): SeatNumber
    {
        return $this->number;
    }

    public function sector(): ?SeatSector
    {
        return $this->sector;
    }

    public function type(): SeatType
    {
        return $this->type;
    }
}
