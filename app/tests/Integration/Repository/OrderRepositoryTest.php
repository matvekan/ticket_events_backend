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

/**
 * Infrastructure layer: DoctrineOrderRepository::findOrderByUserId (joins orders+tickets+event_seats+events+venues).
 */
final class OrderRepositoryTest extends KernelTestCase
{
    public function testFindOrderByUserIdReturnsHydratedDtos(): void
    {
        // Arrange
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $suffix = uniqid();
        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $container->get(ClockInterface::class),
            $container->get(IdGeneratorInterface::class),
            $suffix,
        );
        $container->get(ReserveSeatsHandler::class)(
            new ReserveSeatsCommand($data['user']->rawId(), [$data['eventSeat']->rawId()])
        );

        // Act
        $dtos = $container->get(OrderRepositoryInterface::class)->findOrderByUserId($data['user']->rawId());

        // Assert
        self::assertNotEmpty($dtos);
        self::assertSame('pending', $dtos[0]->status);
        self::assertNotEmpty($dtos[0]->tickets);
    }

    public function testFindOrderByUserIdReturnsEmptyForUnknownUser(): void
    {
        // Arrange
        self::bootKernel();
        $container = static::getContainer();

        // Act
        $dtos = $container->get(OrderRepositoryInterface::class)->findOrderByUserId('00000000-0000-4000-8000-000000000000');

        // Assert
        self::assertSame([], $dtos);
    }
}
