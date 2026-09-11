<?php

declare(strict_types=1);

namespace App\Tests\Functional\Order;

use App\Domain\Entity\Event;
use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Presentation layer: пользовательский флоу бронирования POST /api/orders.
 * Stripe-направление /pay здесь не вызывается (внешний API мокается/избегается).
 */
final class OrderFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $token;
    private string $eventSeatId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');

        $suffix = uniqid();
        $email = sprintf('order_%s@example.com', $suffix);
        $this->registerAndLogin($email);
        $this->makeAdmin($email);
        $venueId = $this->createVenue(sprintf('Order Hall %s', $suffix));
        $this->addSeats($venueId);
        $this->createAndPublishEvent($venueId, sprintf('Order Concert %s', $suffix));
    }

    public function testReserveSeatsReturns201(): void
    {
        // Arrange — свободное место подготовлено в setUp
        // Act
        $this->client->request('POST', '/api/orders', [], [], [], json_encode([
            'seatIds' => [$this->eventSeatId],
        ]));

        // Assert
        self::assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Seats reserved.', $data['message']);
    }

    public function testMyOrdersReturnsReservedOrder(): void
    {
        // Arrange
        $this->client->request('POST', '/api/orders', [], [], [], json_encode([
            'seatIds' => [$this->eventSeatId],
        ]));
        self::assertResponseStatusCodeSame(201);

        // Act
        $this->client->request('GET', '/api/orders/my');

        // Assert
        self::assertResponseIsSuccessful();
        $orders = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNotEmpty($orders);
        self::assertSame('pending', $orders[0]['status']);
    }

    public function testReserveSeatsReturns422OnEmptyList(): void
    {
        // Arrange — пустой список мест
        // Act
        $this->client->request('POST', '/api/orders', [], [], [], json_encode(['seatIds' => []]));

        // Assert (валидация команды → ApiExceptionListener → 422)
        self::assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testReserveSeatsReturns422OnInvalidUuid(): void
    {
        // Arrange — невалидный UUID
        // Act
        $this->client->request('POST', '/api/orders', [], [], [], json_encode(['seatIds' => ['not-a-uuid']]));

        // Assert
        self::assertResponseStatusCodeSame(422);
    }

    public function testGetUnknownOrderReturns404(): void
    {
        // Arrange — несуществующий заказ
        // Act
        $this->client->request('GET', '/api/orders/00000000-0000-4000-8000-000000000000');

        // Assert
        self::assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testOrdersRequireAuthentication(): void
    {
        // Arrange — клиент без токена (перезапускаем kernel для второго клиента)
        static::ensureKernelShutdown();
        $anonymous = static::createClient();
        $anonymous->setServerParameter('CONTENT_TYPE', 'application/json');

        // Act
        $anonymous->request('POST', '/api/orders', [], [], [], json_encode(['seatIds' => [$this->eventSeatId]]));

        // Assert
        self::assertResponseStatusCodeSame(401);
    }

    private function registerAndLogin(string $email): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'name' => 'Test User', 'email' => $email, 'password' => 'password123',
        ]));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => $email, 'password' => 'password123',
        ]));
        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->token = $data['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $this->token));
    }

    private function makeAdmin(string $email): void
    {
        $users = static::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class);
        $user = $users->findByEmail(new Email($email));
        $user->changeRoles(['ROLE_ADMIN']);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function createVenue(string $name): string
    {
        $this->client->request('POST', '/api/venues', [], [], [], json_encode([
            'name' => $name, 'address' => '123 Test Street', 'city' => 'Moscow',
        ]));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/venues');
        $venues = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($venues as $venue) {
            if ($venue['name'] === $name) {
                return $venue['id'];
            }
        }

        self::fail('Venue not found after creation');
    }

    private function addSeats(string $venueId): void
    {
        $this->client->request('POST', sprintf('/api/venues/%s/seats', $venueId), [], [], [], json_encode([
            'seats' => [['row' => 'A', 'number' => 1, 'type' => 'standard', 'sector' => 'Orchestra']],
        ]));
        self::assertResponseStatusCodeSame(201);
    }

    private function createAndPublishEvent(string $venueId, string $title): void
    {
        $this->client->request('GET', sprintf('/api/venues/%s/seats', $venueId));
        $seatId = json_decode($this->client->getResponse()->getContent(), true)[0]['id'];

        $this->client->request('POST', '/api/events', [], [], [], json_encode([
            'title' => $title,
            'description' => 'An amazing test concert event for functional test',
            'date' => '2026-12-01T20:00:00Z',
            'venueId' => $venueId,
            'seats' => [['seatId' => $seatId, 'priceAmount' => 5000]],
        ]));
        self::assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        /** @var Event $event */
        $event = $em->createQueryBuilder()
            ->select('e')->from(Event::class, 'e')->where('e.title = :title')->setParameter('title', $title)
            ->getQuery()->getOneOrNullResult();
        $eventId = $event->id()->toString();

        $this->client->request('POST', sprintf('/api/events/%s/publish', $eventId), [], [], [], '{}');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/events/%s/seats', $eventId));
        $this->eventSeatId = json_decode($this->client->getResponse()->getContent(), true)[0]['id'];
    }
}
