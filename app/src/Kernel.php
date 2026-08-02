<?php

declare(strict_types=1);

namespace App;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueName;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Yokai\DoctrineValueObject\Doctrine\Types;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const array DOCTRINE_VALUE_OBJECTS = [
        'name' => Name::class,
        'email' => Email::class,
        'event_title' => EventTitle::class,
        'event_description' => EventDescription::class,
        'venue_address' => VenueAddress::class,
        'venue_city' => VenueCity::class,
        'venue_name' => VenueName::class,
        'seat_row' => SeatRow::class,
        'seat_number' => SeatNumber::class,
        'seat_sector' => SeatSector::class,
        'ticket_code' => TicketCode::class,
    ];

    /**
     * @throws TypesException
     */
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        new Types(self::DOCTRINE_VALUE_OBJECTS)->register(Type::getTypeRegistry());
    }

    /**
     * @return list<string>
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
