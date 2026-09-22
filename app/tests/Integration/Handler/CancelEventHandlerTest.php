<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Event\CancelEventCommand;
use App\Application\CommandHandler\Event\CancelEventHandler;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\EventCancelledEvent;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CancelEventHandlerTest extends KernelTestCase
{
    public function testCancelPublishedEventTransitionsToCancelledAndWritesOutboxEvents(): void
    {
        $context = $this->createPublishedEventContext();
        $handler = $context['container']->get(CancelEventHandler::class);

        $handler(new CancelEventCommand($context['eventId']));

        $this->assertEventIsCancelled($context['eventId']);
        $this->assertCancelledOutboxEventsExist($context['eventId']);
    }

    public function testCancelEventThrowsEntityNotFoundWhenEventDoesNotExist(): void
    {
        self::bootKernel();
        $handler = static::getContainer()->get(CancelEventHandler::class);

        $this->expectException(EntityNotFoundException::class);

        $handler(new CancelEventCommand('00000000-0000-4000-8000-000000000000'));
    }

    public function testCancelEventThrowsBusinessRuleViolationWhenEventIsAlreadyCancelled(): void
    {
        $context = $this->createPublishedEventContext();
        $handler = $context['container']->get(CancelEventHandler::class);
        $handler(new CancelEventCommand($context['eventId']));

        $this->expectException(\App\Domain\Exception\BusinessRuleViolationException::class);

        $handler(new CancelEventCommand($context['eventId']));
    }

    private function createPublishedEventContext(): array
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

        return ['container' => $container, 'em' => $em, 'eventId' => $data['event']->rawId()];
    }

    private function assertEventIsCancelled(string $eventId): void
    {
        $container = static::getContainer();
        $container->get(EntityManagerInterface::class)->clear();

        $cancelled = $container->get(EventRepositoryInterface::class)->findById(EventId::fromString($eventId));

        self::assertSame(EventStatus::Cancelled, $cancelled->status());
    }

    private function assertCancelledOutboxEventsExist(string $eventId): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        self::assertTrue($this->containsEvent($em, EventCancelledEvent::class, $eventId));
        self::assertTrue($this->containsStatusChangedEvent($em, $eventId, 'cancelled'));
    }

    private function containsEvent(EntityManagerInterface $em, string $class, string $eventId): bool
    {
        $messages = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => $class]);

        foreach ($messages as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof EventCancelledEvent && $event->eventId() === $eventId) {
                return true;
            }
        }

        return false;
    }

    private function containsStatusChangedEvent(EntityManagerInterface $em, string $eventId, string $status): bool
    {
        $messages = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => EventStatusChangedEvent::class]);

        foreach ($messages as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof EventStatusChangedEvent && $event->eventId() === $eventId && $event->newStatus() === $status) {
                return true;
            }
        }

        return false;
    }
}
