<?php

declare(strict_types=1);

namespace App;

use App\Domain\ValueObject\ChatMessageId;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventDescription;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\EventTitle;
use App\Domain\ValueObject\MessageText;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\SeatNumber;
use App\Domain\ValueObject\SeatRow;
use App\Domain\ValueObject\SeatSector;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\TicketCode;
use App\Domain\ValueObject\TicketId;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\VenueAddress;
use App\Domain\ValueObject\VenueCity;
use App\Domain\ValueObject\VenueId;
use App\Domain\ValueObject\VenueName;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\DBAL\Types\Type;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Yokai\DoctrineValueObject\Doctrine\Types;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const array DOCTRINE_VALUE_OBJECTS = [
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
        'message_text' => MessageText::class,
        'user_id' => UserId::class,
        'chat_message_id' => ChatMessageId::class,
        'chat_room_id' => ChatRoomId::class,
        'event_id' => EventId::class,
        'ticket_id' => TicketId::class,
        'seat_id' => SeatId::class,
        'venue_id' => VenueId::class,
        'payment_id' => PaymentId::class,
        'order_id' => OrderId::class,
        'event_seat_id' => EventSeatId::class,
    ];

    public function boot(): void
    {
        parent::boot();
        try {
            new Types(self::DOCTRINE_VALUE_OBJECTS)->register(Type::getTypeRegistry());
        } catch (TypesException $e) {
            throw new \RuntimeException('Failed to register Doctrine value object types: '.$e->getMessage(), 0, $e);
        }
    }
}
