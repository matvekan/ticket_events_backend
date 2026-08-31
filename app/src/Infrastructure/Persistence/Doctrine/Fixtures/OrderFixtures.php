<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\OrderStatus;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\Refund;
use App\Domain\Entity\RefundStatus;
use App\Domain\Entity\User;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\RefundRepositoryInterface;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\Price;
use App\Domain\ValueObject\RefundId;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

final class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly EventRepositoryInterface $events,
        private readonly EventSeatRepositoryInterface $eventSeats,
        private readonly PaymentRepositoryInterface $payments,
        private readonly RefundRepositoryInterface $refunds,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        // Paid orders for demo users.
        $this->reserveAndPay('user_demo', 'event_0', 2, $manager);
        $this->reserveAndPay('user_1', 'event_3', 3, $manager);
        $this->reserveAndPay('user_2', 'event_5', 1, $manager);

        // Pending (reserved only) order.
        $this->reserveOnly('user_3', 'event_1', 1, $manager);

        // Refunded order.
        $this->reserveAndRefund('user_admin', 'event_2', 1, $manager);
    }

    /** @return string[] */
    public function getDependencies(): array
    {
        return [UserFixtures::class, EventFixtures::class];
    }

    private function reserveAndPay(string $userRef, string $eventRef, int $count, ObjectManager $manager): void
    {
        $order = $this->reserveOnly($userRef, $eventRef, $count, $manager);
        if ($order === null) {
            return;
        }

        $userId = $order->userId();

        $payment = new Payment(
            id: PaymentId::generate(),
            orderId: $order->id(),
            userId: $userId,
            amount: $order->totalAmount(),
            status: PaymentStatus::Succeeded,
        );

        $this->payments->save($payment);
        $order->markAsPaid($payment);
        $this->orders->save($order);
        $manager->flush();
    }

    private function reserveAndRefund(string $userRef, string $eventRef, int $count, ObjectManager $manager): void
    {
        $order = $this->reserveOnly($userRef, $eventRef, $count, $manager);
        if ($order === null) {
            return;
        }

        $userId = $order->userId();

        $payment = new Payment(
            id: PaymentId::generate(),
            orderId: $order->id(),
            userId: $userId,
            amount: $order->totalAmount(),
            status: PaymentStatus::Succeeded,
        );

        $this->payments->save($payment);
        $order->markAsPaid($payment);
        $this->orders->save($order);
        $manager->flush();

        $refund = new Refund(
            id: RefundId::generate(),
            orderId: $order->id(),
            paymentId: $payment->id(),
            amount: $order->totalAmount(),
            status: RefundStatus::Succeeded,
        );

        $this->refunds->save($refund);
        $order->markAsRefunded($refund);
        $this->orders->save($order);
        $manager->flush();
    }

    private function reserveOnly(string $userRef, string $eventRef, int $count, ObjectManager $manager): ?Order
    {
        /** @var User $user */
        $user = $this->getReference($userRef, User::class);
        /** @var Event $event */
        $event = $this->getReference($eventRef, Event::class);

        $availableSeats = array_filter(
            $this->eventSeats->findByEventId($event->id()),
            static fn (EventSeat $es): bool => $es->isAvailable()
        );

        if (count($availableSeats) < $count) {
            return null;
        }

        $seatIds = array_map(
            static fn (EventSeat $eventSeat) => $eventSeat->id(),
            array_slice(array_values($availableSeats), 0, $count),
        );

        $order = new Order(
            id: OrderId::generate(),
            userId: $user->id(),
            eventId: $event->id(),
            eventSeatIds: $seatIds,
            status: OrderStatus::Reserved,
            reservedAt: new \DateTimeImmutable(),
        );

        foreach ($seatIds as $seatId) {
            $eventSeat = $this->eventSeats->findById($seatId);
            if ($eventSeat !== null) {
                $eventSeat->reserve($order->id());
                $this->eventSeats->save($eventSeat);
            }
        }

        $this->orders->save($order);
        $manager->flush();

        return $order;
    }
}
