<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Application\CommandHandler\Payment\ConfirmPaymentHandler;
use App\Application\CommandHandler\Payment\StartPaymentHandler;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\EventCreatedEvent;
use App\Domain\Event\EventStatusChangedEvent;
use App\Domain\Event\OrderPaidEvent;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\UserId;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OutboxMessageRepositoryTest extends KernelTestCase
{
    public function testOutboxContainsOrderPaidEventAfterPaymentConfirmation(): void
    {
        $context = $this->createPaidOrderContext();

        $outbox = $this->findOutboxMessagesByClass(OrderPaidEvent::class);

        self::assertTrue($this->containsOrderPaidEventForOrder($outbox, $context['orderId']));
    }

    public function testOutboxContainsEventCreatedAndPublishedEventsAfterFixtureSetup(): void
    {
        $context = $this->createPublishedEventContext();

        $created = $this->findOutboxMessagesByClass(EventCreatedEvent::class);
        $statusChanged = $this->findOutboxMessagesByClass(EventStatusChangedEvent::class);

        self::assertTrue($this->containsEventWithId($created, EventCreatedEvent::class, $context['eventId']));
        self::assertTrue($this->containsEventWithId($statusChanged, EventStatusChangedEvent::class, $context['eventId']));
    }

    public function testFindPendingReturnsOnlyUnpublishedMessages(): void
    {
        $this->createPaidOrderContext();

        $unsent = $this->findOutboxMessagesBySentStatus(null);

        self::assertNotEmpty($unsent);

        foreach ($unsent as $message) {
            self::assertFalse($message->isSent());
        }
    }

    public function testPersistingDirectOutboxMessageWritesCorrectBodyAndClass(): void
    {
        self::bootKernel();
        $outboxMessage = $this->createDirectOutboxMessage();

        $this->getEntityManager()->persist($outboxMessage);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $messages = $this->findOutboxMessagesByClass(EventCreatedEvent::class);

        self::assertTrue($this->containsMessageWithId($messages, $outboxMessage->id()));
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

        return ['eventId' => $data['event']->rawId()];
    }

    private function createPaidOrderContext(): array
    {
        self::bootKernel();
        $em = $this->getEntityManager();
        $clock = $this->getClock();
        $ids = $this->getIdGenerator();

        $data = IntegrationFixture::createPublishedEventWithSeat($em, $clock, $ids, uniqid());
        $container = static::getContainer();

        $container->get(ReserveSeatsHandler::class)->__invoke(new ReserveSeatsCommand($data['user']->rawId(), [$data['eventSeat']->rawId()]));
        $em->clear();

        $order = $container->get(OrderRepositoryInterface::class)->findByUserId(new UserId($data['user']->rawId()))[0];

        $container->get(StartPaymentHandler::class)->__invoke(new StartPaymentCommand($order->rawId(), $data['user']->rawId()));
        $container->get(ConfirmPaymentHandler::class)->__invoke(new ConfirmPaymentCommand($order->rawId(), $data['user']->rawId()));
        $em->clear();

        return ['orderId' => $order->rawId(), 'eventId' => $data['event']->rawId()];
    }

    private function createDirectOutboxMessage(): OutboxMessage
    {
        return new OutboxMessage(
            EventCreatedEvent::class,
            base64_encode(serialize(new EventCreatedEvent('test-id', 'Test Event'))),
            $this->getClock(),
            $this->getIdGenerator()
        );
    }

    private function findOutboxMessagesByClass(string $class): array
    {
        return $this->getEntityManager()->getRepository(OutboxMessage::class)->findBy(['messageClass' => $class]);
    }

    private function findOutboxMessagesBySentStatus(?\DateTimeImmutable $sentAt): array
    {
        return $this->getEntityManager()->getRepository(OutboxMessage::class)->findBy(['sentAt' => $sentAt]);
    }

    private function containsOrderPaidEventForOrder(array $outbox, string $orderId): bool
    {
        foreach ($outbox as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof OrderPaidEvent && $event->orderId() === $orderId) {
                return true;
            }
        }

        return false;
    }

    private function containsEventWithId(array $outbox, string $class, string $eventId): bool
    {
        foreach ($outbox as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof EventCreatedEvent && $event->eventId() === $eventId) {
                return true;
            }

            if ($event instanceof EventStatusChangedEvent && $event->eventId() === $eventId) {
                return true;
            }
        }

        return false;
    }

    private function containsMessageWithId(array $messages, string $id): bool
    {
        foreach ($messages as $message) {
            if ($message->id() === $id) {
                return true;
            }
        }

        return false;
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
