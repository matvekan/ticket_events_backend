<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Application\Command\Admin\RefundOrderCommand;
use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\Event\CreateEventCommand;
use App\Application\Command\Event\PublishEventCommand;
use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Command\Payment\ConfirmPaymentCommand;
use App\Application\Command\Payment\StartPaymentCommand;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Command\Venue\CreateVenueCommand;
use App\Application\Dto\EventSeatData;
use App\Application\Dto\SeatData;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Entity\User;
use App\Domain\Entity\Venue;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\SeatType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class AppFixtures extends Fixture
{
    /**
     * @var array<int, array{name: string, email: string, password: string, roles: string[]}>
     */
    private const USERS = [
        ['name' => 'Администратор', 'email' => 'admin@tickets.by', 'password' => 'admin1234', 'roles' => ['ROLE_ADMIN']],
        ['name' => 'Дмитрий Демо', 'email' => 'demo@tickets.by', 'password' => 'demo1234', 'roles' => []],
        ['name' => 'Анна Смирнова', 'email' => 'anna@tickets.by', 'password' => 'anna1234', 'roles' => []],
        ['name' => 'Иван Петров', 'email' => 'ivan@tickets.by', 'password' => 'ivan1234', 'roles' => []],
        ['name' => 'Мария Кузнецова', 'email' => 'maria@tickets.by', 'password' => 'maria1234', 'roles' => []],
    ];

    /**
     * @var array<int, array{name: string, address: string, city: string, latitude: float, longitude: float}>
     */
    private const VENUES = [
        ['name' => 'Минск-Арена', 'address' => 'пр-т Победителей, 111', 'city' => 'Минск', 'latitude' => 53.9366, 'longitude' => 27.4819],
        ['name' => 'Дворец Спорта', 'address' => 'пр-т Победителей, 4', 'city' => 'Минск', 'latitude' => 53.9106, 'longitude' => 27.5486],
        ['name' => 'Prime Hall', 'address' => 'пр-т Победителей, 20', 'city' => 'Минск', 'latitude' => 53.9139, 'longitude' => 27.5433],
        ['name' => 'Чижовка-Арена', 'address' => 'ул. Ташкентская, 19', 'city' => 'Минск', 'latitude' => 53.8470, 'longitude' => 27.6300],
        ['name' => 'ДК МАЗ', 'address' => 'ул. Долгобродская, 24', 'city' => 'Минск', 'latitude' => 53.8927, 'longitude' => 27.5933],
        ['name' => 'Белгосфилармония', 'address' => 'ул. Ленина, 50', 'city' => 'Минск', 'latitude' => 53.8960, 'longitude' => 27.5590],
    ];

    /**
     * @var array<int, array{title: string, description: string, date: string, venue: string, published: bool, basePrice: int}>
     */
    private const EVENTS = [
        [
            'title' => 'Ночная симфония: рок с оркестром',
            'description' => 'Симфонический оркестр исполняет лучшие рок-хиты: от Queen до Metallica в аранжировках для большого состава. Полтора часа мощного звука, света и живых струнных.',
            'date' => '2026-09-19 19:00:00',
            'venue' => 'Минск-Арена',
            'published' => true,
            'basePrice' => 6000,
        ],
        [
            'title' => 'Джазовые вечера в Минске',
            'description' => 'Камерные джазовые концерты с участием лучших минских музыкантов. Свинг, бибоп и фанк в уютной атмосфере большого зала филармонии.',
            'date' => '2026-09-05 20:00:00',
            'venue' => 'Белгосфилармония',
            'published' => true,
            'basePrice' => 2500,
        ],
        [
            'title' => 'Фестиваль электронной музыки VOLT',
            'description' => 'Крупнейший фестиваль электронной музыки страны: десятки диджеев, три сцены, световое шоу и танцпол до утра. Хедлайнеры анонсируются ближе к дате.',
            'date' => '2026-10-10 18:00:00',
            'venue' => 'Дворец Спорта',
            'published' => true,
            'basePrice' => 4500,
        ],
        [
            'title' => 'Хоккейная классика: Матч всех звёзд',
            'description' => 'Ежегодный гала-матч сильнейших хоккеистов лиги. Звёздный состав, буллитные конкурсы в перерыве и розыгрыш призов для болельщиков на трибунах.',
            'date' => '2026-11-15 17:00:00',
            'venue' => 'Чижовка-Арена',
            'published' => true,
            'basePrice' => 3000,
        ],
        [
            'title' => 'Классика в ДК МАЗ: Шопен и Рахманинов',
            'description' => 'Вечер фортепианной музыки в исполнении лауреатов международных конкурсов. Ноктюрны Шопена и прелюдии Рахманинова в акустике зала ДК МАЗ.',
            'date' => '2026-08-22 19:30:00',
            'venue' => 'ДК МАЗ',
            'published' => true,
            'basePrice' => 2500,
        ],
        [
            'title' => 'Stand Up Comedy Night',
            'description' => 'Сольные стендап-концерты лучших комиков страны в камерном формате. Без купюр, без телеэфира, только живые шутки в зале Prime Hall.',
            'date' => '2026-08-29 20:00:00',
            'venue' => 'Prime Hall',
            'published' => true,
            'basePrice' => 3500,
        ],
        [
            'title' => 'Струнный квартет: Барокко',
            'description' => 'Бах, Вивальди и Гендель в исполнении струнного квартета. Погружение в атмосферу барочной музыки при свечах в малом зале филармонии.',
            'date' => '2026-12-12 19:00:00',
            'venue' => 'Белгосфилармония',
            'published' => false,
            'basePrice' => 2000,
        ],
        [
            'title' => 'Новогодний мюзикл в Минск-Арене',
            'description' => 'Сказочный новогодний мюзикл для всей семьи: сотни костюмов, живой оркестр, спецэффекты и традиционная программа для детей перед началом шоу.',
            'date' => '2026-12-30 18:00:00',
            'venue' => 'Минск-Арена',
            'published' => true,
            'basePrice' => 5000,
        ],
    ];

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly VenueRepositoryInterface $venues,
        private readonly SeatRepositoryInterface $seats,
        private readonly EventRepositoryInterface $events,
        private readonly UserRepositoryInterface $users,
        private readonly OrderRepositoryInterface $orders,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->seedUsers();
        $venueIds = $this->seedVenues();
        $createdEvents = $this->seedEvents($venueIds);
        $this->seedOrders($createdEvents);
    }

    private function seedUsers(): void
    {
        foreach (self::USERS as $userData) {
            $email = new Email($userData['email']);
            if ($this->users->findByEmail($email) !== null) {
                continue;
            }

            $this->commandBus->dispatch(new RegisterUserCommand(
                name: $userData['name'],
                email: $userData['email'],
                password: $userData['password'],
            ));

            if ($userData['roles'] !== []) {
                $user = $this->users->findByEmail($email);
                if ($user !== null) {
                    $this->transactionManager->transactional(function () use ($user, $userData): void {
                        $user->updateRoles(array_merge($userData['roles'], ['ROLE_USER']));
                        $this->users->save($user);
                    });
                }
            }
        }
    }

    /** @return array<string, Uuid> venue name => id */
    private function seedVenues(): array
    {
        $result = [];

        foreach (self::VENUES as $venueData) {
            $existing = $this->findVenueByName($venueData['name']);
            if ($existing !== null) {
                $result[$venueData['name']] = $existing->id();
                continue;
            }

            $this->commandBus->dispatch(new CreateVenueCommand(
                name: $venueData['name'],
                address: $venueData['address'],
                city: $venueData['city'],
                latitude: $venueData['latitude'],
                longitude: $venueData['longitude'],
            ));

            $venue = $this->findVenueByName($venueData['name']);
            if ($venue === null) {
                continue;
            }

            $this->commandBus->dispatch(new AddSeatsToVenueCommand(
                venueId: $venue->id()->toRfc4122(),
                seats: $this->buildVenueSeats(),
            ));

            $result[$venueData['name']] = $venue->id();
        }

        return $result;
    }

    /** @return SeatData[] */
    private function buildVenueSeats(): array
    {
        $seats = [];

        foreach (['A', 'B', 'C', 'D'] as $row) {
            for ($number = 1; $number <= 12; ++$number) {
                $seats[] = new SeatData($row, $number, SeatType::Standard->value);
            }
        }

        for ($number = 1; $number <= 10; ++$number) {
            $seats[] = new SeatData('E', $number, SeatType::VIP->value, 'VIP-зона');
        }

        for ($number = 1; $number <= 6; ++$number) {
            $seats[] = new SeatData('F', $number, SeatType::Premium->value, 'Фан-зона');
        }

        return $seats;
    }

    /** @param array<string, Uuid> $venueIds @return Event[] */
    private function seedEvents(array $venueIds): array
    {
        $created = [];

        foreach (self::EVENTS as $eventData) {
            $existing = $this->findEventByTitle($eventData['title']);
            if ($existing !== null) {
                continue;
            }

            $venueId = $venueIds[$eventData['venue']];

            $this->commandBus->dispatch(new CreateEventCommand(
                title: $eventData['title'],
                description: $eventData['description'],
                date: new \DateTimeImmutable($eventData['date']),
                venueId: $venueId->toRfc4122(),
                seats: $this->buildEventSeatData($venueId, $eventData['basePrice']),
            ));

            $event = $this->findEventByTitle($eventData['title']);
            if ($event === null) {
                continue;
            }

            if ($eventData['published']) {
                $this->commandBus->dispatch(new PublishEventCommand($event->id()->toRfc4122()));
            }

            $created[] = $event;
        }

        return $created;
    }

    /** @return EventSeatData[] */
    private function buildEventSeatData(Uuid $venueId, int $basePrice): array
    {
        $result = [];

        foreach ($this->seats->findByVenueId($venueId) as $seat) {
            $result[] = new EventSeatData(
                seatId: $seat->id()->toRfc4122(),
                priceAmount: $this->priceForType($seat->type(), $basePrice),
            );
        }

        return $result;
    }

    private function priceForType(SeatType $type, int $basePrice): int
    {
        return match ($type) {
            SeatType::Standard => $basePrice,
            SeatType::VIP => (int) round($basePrice * 1.7),
            SeatType::Premium => (int) round($basePrice * 2.5),
        };
    }

    /** @param Event[] $createdEvents */
    private function seedOrders(array $createdEvents): void
    {
        $demo = $this->users->findByEmail(new Email('demo@tickets.by'));
        $anna = $this->users->findByEmail(new Email('anna@tickets.by'));
        $admin = $this->users->findByEmail(new Email('admin@tickets.by'));
        $ivan = $this->users->findByEmail(new Email('ivan@tickets.by'));
        $maria = $this->users->findByEmail(new Email('maria@tickets.by'));

        $byTitle = [];
        foreach ($createdEvents as $event) {
            $byTitle[(string) $event->title()] = $event;
        }

        if ($demo !== null && isset($byTitle['Ночная симфония: рок с оркестром'])) {
            $this->reserveAndPay($demo, $byTitle['Ночная симфония: рок с оркестром'], 2);
        }

        if ($ivan !== null && isset($byTitle['Хоккейная классика: Матч всех звёзд'])) {
            $this->reserveAndPay($ivan, $byTitle['Хоккейная классика: Матч всех звёзд'], 3);
        }

        if ($maria !== null && isset($byTitle['Stand Up Comedy Night'])) {
            $this->reserveAndPay($maria, $byTitle['Stand Up Comedy Night'], 1);
        }

        if ($anna !== null && isset($byTitle['Джазовые вечера в Минске'])) {
            $this->reserveOnly($anna, $byTitle['Джазовые вечера в Минске'], 1);
        }

        if ($admin !== null && isset($byTitle['Фестиваль электронной музыки VOLT'])) {
            $this->reserveAndRefund($admin, $byTitle['Фестиваль электронной музыки VOLT'], 1);
        }
    }

    private function reserveAndPay(User $user, Event $event, int $count): void
    {
        $order = $this->reserveOnly($user, $event, $count);
        if ($order === null) {
            return;
        }

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $user->id()->toRfc4122(),
        ));
        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $user->id()->toRfc4122(),
        ));
    }

    private function reserveAndRefund(User $user, Event $event, int $count): void
    {
        $order = $this->reserveOnly($user, $event, $count);
        if ($order === null) {
            return;
        }

        $this->commandBus->dispatch(new StartPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $user->id()->toRfc4122(),
        ));
        $this->commandBus->dispatch(new ConfirmPaymentCommand(
            orderId: $order->id()->toRfc4122(),
            userId: $user->id()->toRfc4122(),
        ));
        $this->commandBus->dispatch(new RefundOrderCommand($order->id()->toRfc4122()));
    }

    private function reserveOnly(User $user, Event $event, int $count): ?Order
    {
        $seatIds = $event->eventSeats()
            ->filter(fn (EventSeat $eventSeat): bool => $eventSeat->isAvailable())
            ->map(fn (EventSeat $eventSeat): string => $eventSeat->id()->toRfc4122())
            ->slice(0, $count);

        if (\count($seatIds) < $count) {
            return null;
        }

        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $user->id()->toRfc4122(),
            eventSeatIds: array_values($seatIds),
        ));

        return $this->findOrderForEvent($user, $event);
    }

    private function findOrderForEvent(User $user, Event $event): ?Order
    {
        foreach ($this->orders->findByUserId($user->id()) as $order) {
            foreach ($order->tickets() as $ticket) {
                if ($ticket->eventSeat()->event()->id()->equals($event->id())) {
                    return $order;
                }
            }
        }

        return null;
    }

    private function findVenueByName(string $name): ?Venue
    {
        foreach ($this->venues->findAll() as $venue) {
            if ((string) $venue->name() === $name) {
                return $venue;
            }
        }

        return null;
    }

    private function findEventByTitle(string $title): ?Event
    {
        foreach ($this->events->findAll() as $event) {
            if ((string) $event->title() === $title) {
                return $event;
            }
        }

        return null;
    }
}