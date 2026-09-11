<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Entity\ChatRoom;
use App\Domain\Entity\User;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Service\ChatAccessPolicy;
use App\Domain\Service\Order\SeatSelectionValidator;
use App\Tests\Unit\Domain\Support\DomainFixture;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use App\Domain\Entity\Event;

final class DomainServicesTest extends TestCase
{
    public function testSeatValidatorAcceptsFreeSeatsOfPublishedEvent(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($event, $venue, $clock, $ids, 2);
        $validator = new SeatSelectionValidator();

        // Act
        $validator->validate($event->eventSeats(), $clock);

        // Assert (no exception = success)
        self::assertTrue(true);
    }

    public function testSeatValidatorRejectsEmptySelection(): void
    {
        // Arrange
        $validator = new SeatSelectionValidator();
        $clock = DomainFixture::clock();

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $validator->validate([], $clock);
    }

    public function testSeatValidatorRejectsDuplicateSeats(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($event, $venue, $clock, $ids, 1);
        $seats = $event->eventSeats();
        $validator = new SeatSelectionValidator();

        // Act + Assert (same seat twice)
        $this->expectException(BusinessRuleViolationException::class);
        $validator->validate([$seats[0], $seats[0]], $clock);
    }

    public function testSeatValidatorRejectsSeatsFromDifferentEvents(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $eventA = DomainFixture::event($venue, $clock, $ids);
        $eventB = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($eventA, $venue, $clock, $ids, 1);
        $this->publishWithSeats($eventB, $venue, $clock, $ids, 1);
        $validator = new SeatSelectionValidator();

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $validator->validate([$eventA->eventSeats()[0], $eventB->eventSeats()[0]], $clock);
    }

    public function testSeatValidatorRejectsUnavailableSeat(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $this->publishWithSeats($event, $venue, $clock, $ids, 1);
        $seat = $event->eventSeats()[0];
        $seat->reserve();
        $validator = new SeatSelectionValidator();

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $validator->validate([$seat], $clock);
    }

    public function testSeatValidatorRejectsDraftEvent(): void
    {
        // Arrange (event stays Draft but has a seat)
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $venue = DomainFixture::venue($ids);
        $event = DomainFixture::event($venue, $clock, $ids);
        $seat = DomainFixture::seat($venue, $ids);
        $prop = new ReflectionProperty(Event::class, 'eventSeats');
        $prop->setValue($event, [DomainFixture::eventSeat($event, $seat, $ids)]);
        $validator = new SeatSelectionValidator();

        // Act + Assert
        $this->expectException(BusinessRuleViolationException::class);
        $validator->validate($event->eventSeats(), $clock);
    }

    public function testChatAccessAllowsOwner(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $owner = DomainFixture::user($ids);
        $room = ChatRoom::create($owner->id(), $clock, $ids);
        $policy = new ChatAccessPolicy();

        // Act
        $result = $policy->canParticipate($room, $owner);

        // Assert
        self::assertTrue($result);
    }

    public function testChatAccessAllowsSupportAdmin(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $owner = DomainFixture::user($ids);
        $admin = DomainFixture::user($ids, 'admin@example.com');
        $admin->changeRoles(['ROLE_ADMIN']);
        $room = ChatRoom::create($owner->id(), $clock, $ids);
        $policy = new ChatAccessPolicy();

        // Act
        $result = $policy->canParticipate($room, $admin);

        // Assert
        self::assertTrue($result);
    }

    public function testChatAccessDeniesStranger(): void
    {
        // Arrange
        $clock = DomainFixture::clock();
        $ids = DomainFixture::ids();
        $owner = DomainFixture::user($ids);
        $stranger = DomainFixture::user($ids, 'stranger@example.com');
        $room = ChatRoom::create($owner->id(), $clock, $ids);
        $policy = new ChatAccessPolicy();

        // Act
        $result = $policy->canParticipate($room, $stranger);

        // Assert
        self::assertFalse($result);
    }

    public function testIsSupportDetectsAdminRole(): void
    {
        // Arrange
        $ids = DomainFixture::ids();
        $admin = DomainFixture::user($ids, 'admin2@example.com');
        $admin->changeRoles(['ROLE_ADMIN']);
        $plain = DomainFixture::user($ids, 'plain@example.com');
        $policy = new ChatAccessPolicy();

        // Act + Assert
        self::assertTrue($policy->isSupport($admin));
        self::assertFalse($policy->isSupport($plain));
    }

    private function publishWithSeats(Event $event, \App\Domain\Entity\Venue $venue, $clock, $ids, int $count): void
    {
        // Arrange helper: attaches seats then publishes.
        $seats = [];
        for ($i = 0; $i < $count; ++$i) {
            $seats[] = DomainFixture::eventSeat(
                $event,
                DomainFixture::seat($venue, $ids, 'A', $i + 1),
                $ids
            );
        }
        $prop = new ReflectionProperty(Event::class, 'eventSeats');
        $prop->setValue($event, $seats);
        $event->publish($clock);
    }
}
