<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;
use App\Domain\ValueObject\VenueId;

class Seat
{
    private function __construct(
        private SeatId $id,
        private VenueId $venueId,
        private SeatRow $row,
        private SeatNumber $number,
        private SeatType $type,
        private ?SeatSector $sector = null,
    ) {
    }

    public static function create(
        VenueId $venueId,
        SeatRow $row,
        SeatNumber $number,
        SeatType $type,
        IdGeneratorInterface $ids,
        ?SeatSector $sector = null,
    ): self {
        return new self(new SeatId($ids->generate()), $venueId, $row, $number, $type, $sector);
    }

    public function id(): SeatId
    {
        return $this->id;
    }

    public function rawId(): string { return $this->id->toString(); }

    
    public function venueId(): VenueId
    {
        return $this->venueId;
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
