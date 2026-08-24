<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatType;

class Seat
{
    private string $id;
    private Venue $venue;
    private SeatRow $row;
    private SeatNumber $number;
    private ?SeatSector $sector = null;
    private SeatType $type;

    private function __construct(
        SeatId $id,
        Venue $venue,
        SeatRow $row,
        SeatNumber $number,
        SeatType $type,
        ?SeatSector $sector = null,
    ) {
        $this->id = $id->toString();
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
        ?IdGeneratorInterface $ids = null,
        ?SeatId $id = null,
    ): self {
        $seatId = $id ?? new SeatId($ids ? $ids->generate() : \Symfony\Component\Uid\Uuid::v7()->toRfc4122());
        return new self($seatId, $venue, $row, $number, $type, $sector);
    }

    public function id(): SeatId
    {
        return new SeatId($this->id);
    }

    public function rawId(): string { return $this->id; }

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
