<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\Payment;
use App\Domain\Entity\Service\OrderTicketFactoryInterface;
use App\Domain\Entity\User;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\Entity\Ticket;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly EventSeatRepositoryInterface $eventSeats,
        private readonly PaymentRepositoryInterface $payments,
        private readonly UserRepositoryInterface $users,
        private readonly OrderTicketFactoryInterface $orderTicketFactory,
        private readonly ClockInterface $clock,
        private readonly IdGeneratorInterface $ids,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createDemoOrders($manager);
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, EventFixtures::class];
    }

    private function createDemoOrders(ObjectManager $manager): void
    {
        $demoUser = $this->users->findByEmail(new Email('demo@tickets.by'));
        $user1 = $this->getReference('user_1', User::class);
        $user2 = $this->getReference('user_2', User::class);
        $user3 = $this->getReference('user_3', User::class);
        $adminUser = $this->users->findByEmail(new Email('admin@tickets.by'));

        $event0 = $this->getReference('event_0', Event::class);
        $event1 = $this->getReference('event_1', Event::class);
        $event2 = $this->getReference('event_2', Event::class);
        $event3 = $this->getReference('event_3', Event::class);
        $event5 = $this->getReference('event_5', Event::class);

        if ($demoUser) {
            $this->createPaidOrder($demoUser, $event0, 2, $manager);
        }
        $this->createPaidOrder($user1, $event3, 3, $manager);
        $this->createPaidOrder($user2, $event5, 1, $manager);

        $this->createPendingOrder($user3, $event1, $manager);

        if ($adminUser) {
            $this->createRefundedOrder($adminUser, $event2, $manager);
        }
    }

    private function createPaidOrder(User $user, Event $event, int $count, ObjectManager $manager): ?Order
    {
        $availableSeats = $this->getAvailableSeats($event, $count);
        if ($availableSeats === null) {
            return null;
        }

        $order = $this->orderTicketFactory->create($user->id(), $availableSeats, $this->clock, $this->ids);
        $this->orders->save($order);
        $manager->flush();

        $payment = Payment::place($order->id(), $order->totalPrice()->amount(), $this->clock, $this->ids);
        $payment->markPaid($this->clock);
        $this->payments->save($payment);

        $order->pay($this->clock);

        foreach ($availableSeats as $seat) {
            $seat->sell();
        }

        $this->orders->save($order);
        $manager->flush();

        return $order;
    }

    private function createPendingOrder(User $user, Event $event, ObjectManager $manager): void
    {
        $availableSeats = $this->getAvailableSeats($event, 1);
        if ($availableSeats === null) {
            return;
        }

        $order = $this->orderTicketFactory->create($user->id(), $availableSeats, $this->clock, $this->ids);
        $this->orders->save($order);
        $manager->flush();
    }

    private function createRefundedOrder(User $user, Event $event, ObjectManager $manager): void
    {
        $order = $this->createPaidOrder($user, $event, 1, $manager);

        if ($order === null) {
            return;
        }

        $order->refund($this->clock);

        $seatIds = array_map(static fn (Ticket $t): EventSeatId => $t->eventSeatId(), $order->tickets());
        $seats = $this->eventSeats->findByIds($seatIds);

        foreach ($seats as $seat) {
            $seat->unsell();
        }

        $this->orders->save($order);
        $manager->flush();
    }

    /**
     * @return array<int, EventSeat>|null
     */
    private function getAvailableSeats(Event $event, int $count): ?array
    {
        $availableSeats = array_filter(
            $this->eventSeats->findByEventId($event->id()),
            static fn (EventSeat $eventSeat): bool => $eventSeat->isAvailable()
        );

        if (\count($availableSeats) < $count) {
            return null;
        }

        return \array_slice(array_values($availableSeats), 0, $count);
    }
}
