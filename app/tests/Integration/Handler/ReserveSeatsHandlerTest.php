<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\CommandHandler\Order\ReserveSeatsHandler;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Event\SeatsReservedEvent;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\UserId;
use App\Domain\ValueObject\SeatStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReserveSeatsHandlerTest extends KernelTestCase
{
    public function testReserveSeatsPersistsOrderReservesSeatAndWritesOutboxEvent(): void
    {
        $context = $this->createPublishedEventContext();
        $handler = $context['container']->get(ReserveSeatsHandler::class);

        $handler(new ReserveSeatsCommand($context['userId'], [$context['eventSeatId']]));

        $this->assertOrderWasPersistedWithOneTicket($context['userId']);
        $this->assertEventSeatIsReserved($context['eventSeatId']);
        $this->assertSeatsReservedEventWasWrittenToOutbox($context['userId']);
    }

    public function testReserveSeatsThrowsEntityNotFoundWhenSeatIdDoesNotExist(): void
    {
        $context = $this->createPublishedEventContext();
        $handler = $context['container']->get(ReserveSeatsHandler::class);

        $this->expectException(EntityNotFoundException::class);

        $handler(new ReserveSeatsCommand($context['userId'], ['00000000-0000-4000-8000-000000000000']));
    }

    public function testReserveSeatsThrowsBusinessRuleViolationWhenSeatIsAlreadyReserved(): void
    {
        $context = $this->createPublishedEventContext();
        $handler = $context['container']->get(ReserveSeatsHandler::class);
        $handler(new ReserveSeatsCommand($context['userId'], [$context['eventSeatId']]));

        $this->expectException(\App\Domain\Exception\BusinessRuleViolationException::class);

        $handler(new ReserveSeatsCommand($context['userId'], [$context['eventSeatId']]));
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

        return [
            'container' => $container,
            'em' => $em,
            'userId' => $data['user']->rawId(),
            'eventSeatId' => $data['eventSeat']->rawId(),
        ];
    }

    private function assertOrderWasPersistedWithOneTicket(string $userId): void
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $em->clear();

        $orders = $container->get(OrderRepositoryInterface::class)->findByUserId(new UserId($userId));

        self::assertCount(1, $orders);
        self::assertInstanceOf(Order::class, $orders[0]);
        self::assertCount(1, $orders[0]->tickets());
    }

    private function assertEventSeatIsReserved(string $eventSeatId): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $seat = $em->getRepository(EventSeat::class)->find($eventSeatId);

        self::assertSame(SeatStatus::Reserved, $seat->status());
    }

    private function assertSeatsReservedEventWasWrittenToOutbox(string $userId): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $outbox = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => SeatsReservedEvent::class]);

        $found = $this->containsSeatsReservedEventForUser($outbox, $userId);

        self::assertTrue($found);
    }

    private function containsSeatsReservedEventForUser(array $outbox, string $userId): bool
    {
        foreach ($outbox as $message) {
            $event = unserialize(base64_decode($message->body()));

            if ($event instanceof SeatsReservedEvent && $event->userId() === $userId) {
                return true;
            }
        }

        return false;
    }
}
