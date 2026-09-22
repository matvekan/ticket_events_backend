<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EventRepositoryTest extends KernelTestCase
{
    public function testFindByIdReturnsPublishedEventWithAssociatedSeats(): void
    {
        $context = $this->createPublishedEventContext();

        $event = $this->getEventRepository()->findById($context['eventId']);

        self::assertNotNull($event);
        self::assertSame($context['rawId'], $event->rawId());
        self::assertSame(EventStatus::Published, $event->status());
        self::assertCount(1, $event->eventSeats());
    }

    public function testFindByIdReturnsNullWhenEventDoesNotExist(): void
    {
        self::bootKernel();

        $event = $this->getEventRepository()->findById(EventId::fromString('00000000-0000-4000-8000-000000000000'));

        self::assertNull($event);
    }

    public function testFindAllContainsPreviouslyCreatedEvent(): void
    {
        $context = $this->createPublishedEventContext();

        $events = $this->getEventRepository()->findAll();

        self::assertTrue($this->containsEventWithId($events, $context['rawId']));
    }

    public function testSavePersistsStatusChangeFromPublishedToCancelled(): void
    {
        $context = $this->createPublishedEventContext();
        $event = $this->getEventRepository()->findById($context['eventId']);
        $event->cancel($this->getClock());

        $this->getEventRepository()->save($event);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $reloaded = $this->getEventRepository()->findById($context['eventId']);

        self::assertSame(EventStatus::Cancelled, $reloaded->status());
    }

    private function createPublishedEventContext(): array
    {
        self::bootKernel();
        $em = $this->getEntityManager();

        $data = IntegrationFixture::createPublishedEventWithSeat(
            $em,
            $this->getClock(),
            $this->getIdGenerator(),
            uniqid()
        );

        return ['eventId' => $data['event']->id(), 'rawId' => $data['event']->rawId()];
    }

    private function containsEventWithId(array $events, string $rawId): bool
    {
        foreach ($events as $event) {
            if ($event->rawId() === $rawId) {
                return true;
            }
        }

        return false;
    }

    private function getEventRepository(): EventRepositoryInterface
    {
        return static::getContainer()->get(EventRepositoryInterface::class);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function getClock(): ClockInterface
    {
        return static::getContainer()->get(ClockInterface::class);
    }

    private function getIdGenerator(): IdGeneratorInterface
    {
        return static::getContainer()->get(IdGeneratorInterface::class);
    }
}
