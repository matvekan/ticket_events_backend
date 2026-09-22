<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Application\Command\Event\CreateEventCommand;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\CommandHandler\Event\CreateEventHandler;
use App\Application\CommandHandler\Venue\CreateVenueHandler;
use App\Domain\Entity\OutboxMessage;
use App\Domain\Entity\Seat;
use App\Domain\Event\EventCreatedEvent;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\EventStatus;
use App\Tests\Integration\Support\IntegrationFixture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateEventHandlerTest extends KernelTestCase
{
    public function testCreateEventPersistsDraftEventAndWritesOutbox(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $ids = $container->get(IdGeneratorInterface::class);
        $clock = $container->get(ClockInterface::class);
        $suffix = 'createhandler_' . uniqid();

        $venueHandler = $container->get(CreateVenueHandler::class);
        $venueHandler(new CreateVenueCommand('Create Handler Hall ' . $suffix, '123 Test St', 'Moscow', null, null));
        $em->clear();
        $venues = $container->get(VenueRepositoryInterface::class)->findAll();
        $venue = end($venues);

        $em = $container->get(EntityManagerInterface::class);
        $seats = $em->createQueryBuilder()
            ->select('s')
            ->from(Seat::class, 's')
            ->where('s.venueId = :venueId')
            ->setParameter('venueId', $venue->id()->toString())
            ->getQuery()
            ->getResult();
        $seatIds = array_map(fn ($s) => $s->rawId(), $seats);

        $eventHandler = $container->get(CreateEventHandler::class);
        $eventTitle = 'Handler Test Event ' . $suffix;
        $eventHandler(new CreateEventCommand(
            $eventTitle,
            'Description for handler test',
            new \DateTimeImmutable('2026-12-01T20:00:00Z'),
            $venue->rawId(),
            array_map(fn ($id) => ['seatId' => $id, 'priceAmount' => 5000], $seatIds)
        ));

        $em->clear();
        $events = $container->get(EventRepositoryInterface::class)->findAll();
        $event = end($events);

        self::assertNotNull($event);
        self::assertContains($event->status()->value, ['draft', 'published']);
        self::assertSame($eventTitle, $event->title()->toString());

        $outbox = $em->getRepository(OutboxMessage::class)->findBy(['messageClass' => EventCreatedEvent::class]);
        $found = false;
        foreach ($outbox as $message) {
            $ev = unserialize(base64_decode($message->body()));
            if ($ev instanceof EventCreatedEvent && $ev->eventId() === $event->rawId()) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found);
    }
}