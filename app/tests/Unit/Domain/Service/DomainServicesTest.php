<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\Event;
use App\Domain\Entity\Venue;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Service\ChatAccessPolicy;
use App\Domain\Service\Order\SeatSelectionValidator;
use App\Tests\Unit\Domain\Support\DomainFixture;
use App\Tests\Unit\Domain\Support\FixedClock;
use App\Tests\Unit\Domain\Support\FixedIdGenerator;
use PHPUnit\Framework\TestCase;

final class DomainServicesTest extends TestCase
{
    public function testSeatValidatorAcceptsFreeSeatsOfPublishedEvent(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($event, $venue, $clock, $ids, 2);
        $validator = new SeatSelectionValidator();

        $validator->validate($event->eventSeats(), $clock);

        self::assertTrue(true);
    }

    public function testSeatValidatorRejectsEmptySelection(): void
    {
        $validator = new SeatSelectionValidator();
        $clock = DomainFixture::clock();

        $this->expectException(BusinessRuleViolationException::class);

        $validator->validate([], $clock);
    }

    public function testSeatValidatorRejectsDuplicateSeats(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($event, $venue, $clock, $ids, 1);
        $seats = $event->eventSeats();
        $validator = new SeatSelectionValidator();

        $this->expectException(BusinessRuleViolationException::class);

        $validator->validate([$seats[0], $seats[0]], $clock);
    }

    public function testSeatValidatorRejectsSeatsFromDifferentEvents(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $eventA = DomainFixture::event($venue, $clock, $ids);
        $eventB = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($eventA, $venue, $clock, $ids, 1);
        $this->publishWithSeats($eventB, $venue, $clock, $ids, 1);
        $validator = new SeatSelectionValidator();

        $this->expectException(BusinessRuleViolationException::class);

        $validator->validate([$eventA->eventSeats()[0], $eventB->eventSeats()[0]], $clock);
    }

    public function testSeatValidatorRejectsUnavailableSeat(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($event, $venue, $clock, $ids, 1);
        $seat = $event->eventSeats()[0];
        $seat->reserve();
        $validator = new SeatSelectionValidator();

        $this->expectException(BusinessRuleViolationException::class);

        $validator->validate([$seat], $clock);
    }

    public function testSeatValidatorRejectsDraftEvent(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seat = DomainFixture::seat($venue, $ids);
        $prop = new \ReflectionProperty(Event::class, 'eventSeats');
        $prop->setValue($event, [DomainFixture::eventSeat($event, $seat, $ids)]);
        $validator = new SeatSelectionValidator();

        $this->expectException(BusinessRuleViolationException::class);

        $validator->validate($event->eventSeats(), $clock);
    }

    public function testChatAccessAllowsOwner(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $owner = DomainFixture::user($ids);
        $room = ChatRoom::create($owner->id(), $clock, $ids);
        $policy = new ChatAccessPolicy();

        $result = $policy->canParticipate($room, $owner);

        self::assertTrue($result);
    }

    public function testChatAccessAllowsSupportAdmin(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $owner = DomainFixture::user($ids);
        $admin = DomainFixture::user($ids, 'admin@example.com');
        $admin->changeRoles(['ROLE_ADMIN']);
        $room = ChatRoom::create($owner->id(), $clock, $ids);
        $policy = new ChatAccessPolicy();

        $result = $policy->canParticipate($room, $admin);

        self::assertTrue($result);
    }

    public function testChatAccessDeniesStranger(): void
    {
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $owner = DomainFixture::user($ids);
        $stranger = DomainFixture::user($ids, 'stranger@example.com');
        $room = ChatRoom::create($owner->id(), $clock, $ids);
        $policy = new ChatAccessPolicy();

        $result = $policy->canParticipate($room, $stranger);

        self::assertFalse($result);
    }

    public function testIsSupportDetectsAdminRole(): void
    {
        $ids = DomainFixture::ids();
        $admin = DomainFixture::user($ids, 'admin2@example.com');
        $admin->changeRoles(['ROLE_ADMIN']);
        $plain = DomainFixture::user($ids, 'plain@example.com');
        $policy = new ChatAccessPolicy();

        self::assertTrue($policy->isSupport($admin));
        self::assertFalse($policy->isSupport($plain));
    }

    private function publishWithSeats(Event $event, Venue $venue, FixedClock $clock, FixedIdGenerator $ids, int $count): void
    {
        $seats = [];
        for ($i = 0; $i < $count; ++$i) {
            $seats[] = DomainFixture::eventSeat(
                $event,
                DomainFixture::seat($venue, $ids, 'A', $i + 1),
                $ids
            );
        }
        $prop = new \ReflectionProperty(Event::class, 'eventSeats');
        $prop->setValue($event, $seats);
        $event->publish($clock);
    }
}
