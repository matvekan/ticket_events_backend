<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrderRepositoryTest extends KernelTestCase
{
    public function testFindOrderByUserIdReturnsHydratedDtoWithTicketsForExistingUser(): void
    {
        $context = $this->createReservedOrderContext();

        $dtos = $this->getOrderRepository()->findOrderByUserId($context['userId']);

        self::assertNotEmpty($dtos);
        self::assertSame('pending', $dtos[0]->status);
        self::assertNotEmpty($dtos[0]->tickets);
    }

    public function testFindOrderByUserIdReturnsEmptyArrayWhenUserHasNoOrders(): void
    {
        self::bootKernel();

        $dtos = $this->getOrderRepository()->findOrderByUserId('00000000-0000-4000-8000-000000000000');

        self::assertSame([], $dtos);
    }

    public function testFindByUserIdReturnsOrdersWithTicketsEagerlyLoaded(): void
    {
        $context = $this->createReservedOrderContext();

        $orders = $this->getOrderRepository()->findByUserId(new \App\Domain\ValueObject\UserId($context['userId']));

        self::assertCount(1, $orders);
        self::assertCount(1, $orders[0]->tickets());
    }

    private function createReservedOrderContext(): array
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(ClockInterface::class),
            $container->get(IdGeneratorInterface::class),
            uniqid()
        );

        $container->get(ReserveSeatsHandler::class)->__invoke(new ReserveSeatsCommand($data['user']->rawId(), [$data['eventSeat']->rawId()]));

        return ['userId' => $data['user']->rawId()];
    }

    private function getOrderRepository(): OrderRepositoryInterface
    {
        return static::getContainer()->get(OrderRepositoryInterface::class);
    }
}
