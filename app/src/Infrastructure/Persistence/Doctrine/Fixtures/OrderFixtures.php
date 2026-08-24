<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Fixtures;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Repository\OrderRepositoryInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly OrderRepositoryInterface $orders,
    ) {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        // Paid orders for demo users.
        $this->reserveAndPay('user_demo', 'event_0', 2);
        $this->reserveAndPay('user_1', 'event_3', 3);
        $this->reserveAndPay('user_2', 'event_5', 1);

        // Pending (reserved only) order.
        $this->reserveOnly('user_3', 'event_1', 1);

        // Refunded order.
        $this->reserveAndRefund('user_admin', 'event_2', 1);
    }

    /** @return string[] */
    public function getDependencies(): array
    {
        return [UserFixtures::class, EventFixtures::class];
    }

    private function reserveAndPay(string $userRef, string $eventRef, int $count): void
    {
        $order = $this->reserveOnly($userRef, $eventRef, $count);
        if ($order === null) {
            return;
        }

        $userId = $order->userId()->toRfc4122();

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $userId,
        ));
        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $userId,
        ));
    }

    private function reserveAndRefund(string $userRef, string $eventRef, int $count): void
    {
        $order = $this->reserveOnly($userRef, $eventRef, $count);
        if ($order === null) {
            return;
        }

        $userId = $order->userId()->toRfc4122();

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $userId,
        ));
        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $userId,
        ));
        $this->commandBus->dispatch(new RefundOrderCommand($order->id()->toRfc4122()));
    }

    private function reserveOnly(string $userRef, string $eventRef, int $count): ?Order
    {
        /** @var User $user */
        $user = $this->getReference($userRef, User::class);
        /** @var Event $event */
        $event = $this->getReference($eventRef, Event::class);

        $seatIds = array_map(
            static fn (EventSeat $eventSeat): string => $eventSeat->id()->toRfc4122(),
            array_filter($event->eventSeats(), static fn (EventSeat $es): bool => $es->isAvailable()),
        );
        $seatIds = array_slice(array_values($seatIds), 0, $count);

        if (count($seatIds) < $count) {
            return null;
        }

        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $user->id()->toRfc4122(),
            eventSeatIds: $seatIds,
        ));

        return $this->findLatestOrderFor($user);
    }

    private function findLatestOrderFor(User $user): ?Order
    {
        $orders = $this->orders->findByUserId($user->id());

        return $orders[0] ?? null;
    }
}
