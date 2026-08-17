<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Command\Order\CancelOrderCommand;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Dto\EventSeatData;
use App\Application\Dto\SeatData;
use App\Application\Service\Auth\UserService;
use App\Application\Service\Event\EventService;
use App\Application\Service\Order\PaymentService;
use App\Application\Service\Venue\VenueService;
use App\Application\Transaction\TransactionManagerInterface;
use App\Domain\Entity\Event;
use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Seat;
use App\Domain\Entity\User;
use App\Domain\Entity\Venue;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\SeatRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\VenueRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\SeatType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:seed-demo-data',
    description: 'Заполняет базу демо-данными: реальные площадки Минска, события, пользователи и заказы.',
)]
final class SeedDemoDataCommand extends Command
{
    /** @var array<int, array{name: string, address: string, city: string, latitude: float, longitude: float}> */
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
            'basePrice' => 600000,
        ],
        [
            'title' => 'Джазовые вечера в Минске',
            'description' => 'Камерные джазовые концерты с участием лучших минских музыкантов. Свинг, бибоп и фанк в уютной атмосфере большого зала филармонии.',
            'date' => '2026-09-05 20:00:00',
            'venue' => 'Белгосфилармония',
            'published' => true,
            'basePrice' => 250000,
        ],
        [
            'title' => 'Фестиваль электронной музыки VOLT',
            'description' => 'Крупнейший фестиваль электронной музыки страны: десятки диджеев, три сцены, световое шоу и танцпол до утра. Хедлайнеры анонсируются ближе к дате.',
            'date' => '2026-10-10 18:00:00',
            'venue' => 'Дворец Спорта',
            'published' => true,
            'basePrice' => 450000,
        ],
        [
            'title' => 'Хоккейная классика: Матч всех звёзд',
            'description' => 'Ежегодный гала-матч сильнейших хоккеистов лиги. Звёздный состав, буллитные конкурсы в перерыве и розыгрыш призов для болельщиков на трибунах.',
            'date' => '2026-11-15 17:00:00',
            'venue' => 'Чижовка-Арена',
            'published' => true,
            'basePrice' => 300000,
        ],
        [
            'title' => 'Классика в ДК МАЗ: Шопен и Рахманинов',
            'description' => 'Вечер фортепианной музыки в исполнении лауреатов международных конкурсов. Ноктюрны Шопена и прелюдии Рахманинова в акустике зала ДК МАЗ.',
            'date' => '2026-08-22 19:30:00',
            'venue' => 'ДК МАЗ',
            'published' => true,
            'basePrice' => 250000,
        ],
        [
            'title' => 'Stand Up Comedy Night',
            'description' => 'Сольные стендап-концерты лучших комиков страны в камерном формате. Без купюр, без телеэфира, только живые шутки в зале Prime Hall.',
            'date' => '2026-08-29 20:00:00',
            'venue' => 'Prime Hall',
            'published' => true,
            'basePrice' => 350000,
        ],
        [
            'title' => 'Струнный квартет: Барокко',
            'description' => 'Бах, Вивальди и Гендель в исполнении струнного квартета. Погружение в атмосферу барочной музыки при свечах в малом зале филармонии.',
            'date' => '2026-12-12 19:00:00',
            'venue' => 'Белгосфилармония',
            'published' => false,
            'basePrice' => 200000,
        ],
        [
            'title' => 'Новогодний мюзикл в Минск-Арене',
            'description' => 'Сказочный новогодний мюзикл для всей семьи: сотни костюмов, живой оркестр, спецэффекты и традиционная программа для детей перед началом шоу.',
            'date' => '2026-12-30 18:00:00',
            'venue' => 'Минск-Арена',
            'published' => true,
            'basePrice' => 500000,
        ],
    ];

    public function __construct(
        private readonly VenueService $venueService,
        private readonly EventService $eventService,
        private readonly UserService $userService,
        private readonly PaymentService $paymentService,
        private readonly VenueRepositoryInterface $venues,
        private readonly SeatRepositoryInterface $seats,
        private readonly EventRepositoryInterface $events,
        private readonly UserRepositoryInterface $users,
        private readonly OrderRepositoryInterface $orders,
        private readonly MessageBusInterface $commandBus,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Демо-данные: площадки Минска, события, пользователи, заказы');

        $this->seedUsers($io);
        $venueIds = $this->seedVenues($io);
        $this->seedEvents($io, $venueIds);
        $this->seedOrders($io);

        $io->success('Сид завершён. Демо-аккаунты: admin@tickets.by / admin1234 (админ), demo@tickets.by / demo1234, anna@tickets.by / anna1234. Пароли: см. ниже.');

        return Command::SUCCESS;
    }

    private function seedUsers(SymfonyStyle $io): void
    {
        $users = [
            ['name' => 'Администратор', 'email' => 'admin@tickets.by', 'password' => 'admin1234', 'admin' => true],
            ['name' => 'Дмитрий Демо', 'email' => 'demo@tickets.by', 'password' => 'demo1234', 'admin' => false],
            ['name' => 'Анна Смирнова', 'email' => 'anna@tickets.by', 'password' => 'anna1234', 'admin' => false],
        ];

        foreach ($users as $userData) {
            if ($this->users->findByEmail(new Email($userData['email'])) !== null) {
                $io->text(sprintf('Пользователь %s уже существует — пропускаю.', $userData['email']));
                continue;
            }

            $this->userService->register($userData['name'], $userData['email'], $userData['password']);

            $user = $this->users->findByEmail(new Email($userData['email']));
            if ($userData['admin'] && $user !== null) {
                $this->transactionManager->transactional(function () use ($user): void {
                    $user->updateRoles(['ROLE_ADMIN', 'ROLE_USER']);
                    $this->users->save($user);
                });
            }

            $io->text(sprintf('Создан пользователь %s (%s).', $userData['email'], $userData['name']));
        }
    }

    /** @return array<string, Uuid> venue name => id */
    private function seedVenues(SymfonyStyle $io): array
    {
        $existing = [];
        foreach ($this->venues->findAll() as $venue) {
            $existing[(string) $venue->name()] = $venue->id();
        }

        $result = [];

        foreach (self::VENUES as $venueData) {
            if (isset($existing[$venueData['name']])) {
                $io->text(sprintf('Площадка %s уже существует — пропускаю.', $venueData['name']));
                $result[$venueData['name']] = $existing[$venueData['name']];
                continue;
            }

            $this->venueService->create(
                $venueData['name'],
                $venueData['address'],
                $venueData['city'],
                $venueData['latitude'],
                $venueData['longitude'],
            );

            $venue = $this->findVenueByName($venueData['name']);
            if ($venue === null) {
                $io->error(sprintf('Не удалось найти созданную площадку %s.', $venueData['name']));
                continue;
            }

            $this->addVenueSeats($venue);
            $result[$venueData['name']] = $venue->id();
            $io->text(sprintf('Создана площадка %s (%s) с залом.', $venueData['name'], $venueData['address']));
        }

        return $result;
    }

    private function addVenueSeats(Venue $venue): void
    {
        $seatData = [];

        foreach (['A', 'B', 'C', 'D'] as $row) {
            for ($number = 1; $number <= 12; ++$number) {
                $seatData[] = new SeatData($row, $number, SeatType::Standard->value);
            }
        }

        for ($number = 1; $number <= 10; ++$number) {
            $seatData[] = new SeatData('E', $number, SeatType::VIP->value, 'VIP-зона');
        }

        for ($number = 1; $number <= 6; ++$number) {
            $seatData[] = new SeatData('F', $number, SeatType::Premium->value, 'Фан-зона');
        }

        $this->venueService->addSeats($venue->id(), $seatData);
    }

    /** @param array<string, Uuid> $venueIds */
    private function seedEvents(SymfonyStyle $io, array $venueIds): void
    {
        $existingTitles = [];
        foreach ($this->events->findAll() as $event) {
            $existingTitles[(string) $event->title()] = true;
        }

        foreach (self::EVENTS as $eventData) {
            if (isset($existingTitles[$eventData['title']])) {
                $io->text(sprintf('Событие «%s» уже существует — пропускаю.', $eventData['title']));
                continue;
            }

            $venueId = $venueIds[$eventData['venue']];
            $eventSeatData = $this->buildEventSeatData($venueId, $eventData['basePrice']);

            $this->eventService->create(
                $eventData['title'],
                $eventData['description'],
                new \DateTimeImmutable($eventData['date']),
                $venueId,
                $eventSeatData,
            );

            $event = $this->findEventByTitle($eventData['title']);
            if ($event === null) {
                $io->error(sprintf('Не удалось найти созданное событие «%s».', $eventData['title']));
                continue;
            }

            if ($eventData['published']) {
                $this->eventService->publish($event->id());
            }

            $io->text(sprintf(
                'Создано событие «%s» (%s мест, %s%s).',
                $eventData['title'],
                \count($event->eventSeats()),
                $eventData['venue'],
                $eventData['published'] ? '' : ', черновик',
            ));
        }
    }

    /** @return EventSeatData[] */
    private function buildEventSeatData(Uuid $venueId, int $basePrice): array
    {
        $result = [];

        foreach ($this->seats->findByVenueId($venueId) as $seat) {
            $result[] = new EventSeatData($seat->id(), $this->priceForType($seat->type(), $basePrice));
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

    private function seedOrders(SymfonyStyle $io): void
    {
        $demo = $this->users->findByEmail(new Email('demo@tickets.by'));
        $anna = $this->users->findByEmail(new Email('anna@tickets.by'));
        $admin = $this->users->findByEmail(new Email('admin@tickets.by'));

        if ($demo === null || $anna === null || $admin === null) {
            $io->warning('Пользователи для заказов не найдены — заказы пропущены.');
            return;
        }

        $symphony = $this->findEventByTitle('Ночная симфония: рок с оркестром');
        $jazz = $this->findEventByTitle('Джазовые вечера в Минске');
        $volt = $this->findEventByTitle('Фестиваль электронной музыки VOLT');

        if ($symphony !== null) {
            $this->reserveAndPay($io, $demo, $symphony, 2, 'Оплаченный заказ (демо-пользователь)');
        }

        if ($jazz !== null) {
            $this->reserveOnly($io, $anna, $jazz, 1, 'Ожидающий оплаты заказ (анна)');
        }

        if ($volt !== null) {
            $orderId = $this->reserveOnly($io, $admin, $volt, 1, 'Отменённый заказ (админ)');
            if ($orderId !== null) {
                $this->commandBus->dispatch(new CancelOrderCommand($orderId, $admin->id()));
                $io->text('Заказ админа отменён.');
            }
        }
    }

    private function reserveAndPay(SymfonyStyle $io, User $user, Event $event, int $count, string $label): void
    {
        $orderId = $this->reserveOnly($io, $user, $event, $count, $label);
        if ($orderId === null) {
            return;
        }

        $this->paymentService->startPayment($orderId, $user->id());
        $this->paymentService->confirmPayment($orderId, $user->id());

        $io->text(sprintf('%s: заказ %s оплачен.', $label, $orderId->toRfc4122()));
    }

    private function reserveOnly(SymfonyStyle $io, User $user, Event $event, int $count, string $label): ?Uuid
    {
        $seatIds = $event->eventSeats()
            ->filter(fn (EventSeat $eventSeat): bool => $eventSeat->isAvailable())
            ->map(fn (EventSeat $eventSeat): Uuid => $eventSeat->id())
            ->slice(0, $count);

        if (\count($seatIds) < $count) {
            $io->warning(sprintf('%s: недостаточно свободных мест.', $label));
            return null;
        }

        $this->commandBus->dispatch(new ReserveSeatsCommand($user->id(), $seatIds));

        $order = $this->orders->findByUserId($user->id())[0] ?? null;
        if ($order === null) {
            $io->error(sprintf('%s: заказ не найден.', $label));
            return null;
        }

        $io->text(sprintf('%s: заказ %s на %d мест.', $label, $order->id()->toRfc4122(), $count));

        return $order->id();
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
