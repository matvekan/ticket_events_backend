<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Event\PublishEventCommand;
use App\Application\CommandHandler\Event\PublishEventHandler;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\SeatStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Infrastructure layer: DoctrineEventSeatRepository with real PostgreSQL.
 */
final class EventSeatRepositoryTest extends KernelTestCase
{
    public function testFindByIdReturnsEventSeatWithRelations(): void
    {
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

        $eventSeat = $container->get(EventSeatRepositoryInterface::class)->findById($data['eventSeat']->id());

        self::assertNotNull($eventSeat);
        self::assertSame($data['eventSeat']->rawId(), $eventSeat->rawId());
        self::assertSame(SeatStatus::Free, $eventSeat->status());
        self::assertNotNull($eventSeat->event());
        self::assertNotNull($eventSeat->seat());
    }

    public function testFindByIdReturnsNullForUnknown(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $seat = $container->get(EventSeatRepositoryInterface::class)->findById(
            \App\Domain\ValueObject\EventSeatId::fromString('00000000-0000-4000-8000-000000000000')
        );

        self::assertNull($seat);
    }

    public function testLockAndFindByIdsReturnsLockedSeats(): void
    {
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

        $transactionManager = $container->get(TransactionManagerInterface::class);
        $transactionManager->transactional(function () use ($container, $data): void {
            $seats = $container->get(EventSeatRepositoryInterface::class)->lockAndFindByIds([
                $data['eventSeat']->id(),
            ]);

            self::assertCount(1, $seats);
            self::assertSame($data['eventSeat']->rawId(), $seats[0]->rawId());
        });
    }

    public function testLockAndFindByIdsReturnsEmptyForUnknown(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $transactionManager = $container->get(TransactionManagerInterface::class);
        $transactionManager->transactional(function () use ($container): void {
            $seats = $container->get(EventSeatRepositoryInterface::class)->lockAndFindByIds([
                \App\Domain\ValueObject\EventSeatId::fromString('00000000-0000-4000-8000-000000000000'),
            ]);

            self::assertSame([], $seats);
        });
    }

    public function testSavePersistsStatusChanges(): void
    {
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
        $seat = $data['eventSeat'];
        $seat->reserve();
        $container->get(EventSeatRepositoryInterface::class)->save($seat);
        $em->flush();
        $em->clear();

        $reloaded = $container->get(EventSeatRepositoryInterface::class)->findById($seat->id());

        self::assertSame(SeatStatus::Reserved, $reloaded->status());
    }

    public function testFindAvailableByEventIdReturnsOnlyFreeSeats(): void
    {
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

        $seats = $container->get(EventSeatRepositoryInterface::class)->findAvailableByEventId($data['event']->id());

        self::assertCount(1, $seats);
        self::assertSame(SeatStatus::Free, $seats[0]->status());
    }
}