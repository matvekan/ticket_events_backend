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
    private string $id;
    private string $venueId;
    private SeatRow $row;
    private SeatNumber $number;
    private ?SeatSector $sector = null;
    private SeatType $type;

    private function __construct(
        SeatId $id,
        VenueId $venueId,
        SeatRow $row,
        SeatNumber $number,
        SeatType $type,
        ?SeatSector $sector = null,
    ) {
        $this->id = $id->toString();
        $this->venueId = $venueId->toString();
        $this->row = $row;
        $this->number = $number;
        $this->type = $type;
        $this->sector = $sector;
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
        return new SeatId($this->id);
    }

    public function rawId(): string { return $this->id; }

    /**
     * Reference to the Venue aggregate by ID (cross-aggregate boundary).
     */
    public function venueId(): VenueId
    {
        return new VenueId($this->venueId);
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
