<?php

declare(strict_types=1);

namespace App\Tests\Functional\Order;

use App\Domain\Entity\Event;
use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OrderFlowTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $eventSeatId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
        $this->prepareAuthenticatedEventContext();
    }

    public function testReserveSeatsReturns201WhenPayloadIsValid(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => [$this->eventSeatId]]));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Seats reserved.', $this->decodeJson()['message']);
    }

    public function testReserveSeatsReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $anonymous = $this->createAnonymousClient();

        $anonymous->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => [$this->eventSeatId]]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testReserveSeatsReturns422WhenSeatListIsEmpty(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => []]));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('error', $this->decodeJson());
    }

    public function testReserveSeatsReturns422WhenSeatIdIsNotValidUuid(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => ['not-a-uuid']]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testReserveSeatsReturns404WhenSeatDoesNotExist(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => ['00000000-0000-4000-8000-000000000000']]));

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetMyOrdersReturns200WithPendingOrderAfterReservation(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => [$this->eventSeatId]]));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/orders/my');

        self::assertResponseIsSuccessful();
        $orders = $this->decodeJson();
        self::assertNotEmpty($orders);
        self::assertSame('pending', $orders[0]['status']);
    }

    public function testGetMyOrdersReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $anonymous = $this->createAnonymousClient();

        $anonymous->request('GET', '/api/orders/my');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetOrderReturns200ForOwnedOrder(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => [$this->eventSeatId]]));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/orders/my');
        $orderId = $this->decodeJson()[0]['id'];

        $this->client->request('GET', sprintf('/api/orders/%s', $orderId));

        self::assertResponseIsSuccessful();
        self::assertSame($orderId, $this->decodeJson()['id']);
    }

    public function testGetOrderReturns404WhenOrderDoesNotExist(): void
    {
        $this->client->request('GET', '/api/orders/00000000-0000-4000-8000-000000000000');

        self::assertResponseStatusCodeSame(404);
        self::assertArrayHasKey('error', $this->decodeJson());
    }

    public function testGetOrderReturns404WhenAuthenticatedAsDifferentUser(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => [$this->eventSeatId]]));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/orders/my');
        $orderId = $this->decodeJson()[0]['id'];

        $otherClient = $this->createAuthenticatedClient($this->uniqueEmail('other_order_user'));

        $otherClient->request('GET', sprintf('/api/orders/%s', $orderId));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCancelOrderReturns200ForOwnedPendingOrder(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], $this->encodeJson(['seatIds' => [$this->eventSeatId]]));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/orders/my');
        $orderId = $this->decodeJson()[0]['id'];

        $this->client->request('POST', sprintf('/api/orders/%s/cancel', $orderId), [], [], [], '{}');

        self::assertResponseIsSuccessful();
    }

    public function testCancelOrderReturns404WhenOrderDoesNotExist(): void
    {
        $this->client->request('POST', '/api/orders/00000000-0000-4000-8000-000000000000/cancel', [], [], [], '{}');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCancelOrderReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $anonymous = $this->createAnonymousClient();

        $anonymous->request('POST', '/api/orders/00000000-0000-4000-8000-000000000000/cancel', [], [], [], '{}');

        self::assertResponseStatusCodeSame(401);
    }

    private function prepareAuthenticatedEventContext(): void
    {
        $email = $this->uniqueEmail('order');
        $this->registerLoginAndPromoteToManager($email);

        $venueId = $this->createVenue('Order Hall ' . uniqid());
        $this->addSeats($venueId);
        $this->createAndPublishEvent($venueId, 'Order Concert ' . uniqid());
    }

    private function registerLoginAndPromoteToManager(string $email): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['name' => 'Test User', 'email' => $email, 'password' => 'password123']));
        self::assertResponseStatusCodeSame(201);

        $users = static::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class);
        $user = $users->findByEmail(new Email($email));
        $user->changeRoles(['ROLE_ADMIN']);
        static::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->client->request('POST', '/api/admin/login', [], [], [], $this->encodeJson(['email' => $email, 'password' => 'password123']));
        self::assertResponseIsSuccessful();

        $token = $this->decodeJson()['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    private function createAuthenticatedClient(string $email): KernelBrowser
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameter('CONTENT_TYPE', 'application/json');
        $client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['name' => 'Test User', 'email' => $email, 'password' => 'password123']));
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/auth/login', [], [], [], $this->encodeJson(['email' => $email, 'password' => 'password123']));
        $token = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        return $client;
    }

    private function createAnonymousClient(): KernelBrowser
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameter('CONTENT_TYPE', 'application/json');

        return $client;
    }

    private function createVenue(string $name): string
    {
        $this->client->request('POST', '/api/venues', [], [], [], $this->encodeJson(['name' => $name, 'address' => '123 Test Street', 'city' => 'Moscow']));
        self::assertResponseStatusCodeSame(201);

        $this->client->request('GET', '/api/venues');
        $venues = $this->decodeJson();

        foreach ($venues as $venue) {
            if ($venue['name'] === $name) {
                return $venue['id'];
            }
        }

        self::fail('Venue not found');

        return '';
    }

    private function addSeats(string $venueId): void
    {
        $this->client->request('POST', sprintf('/api/venues/%s/seats', $venueId), [], [], [], $this->encodeJson(['seats' => [['row' => 'A', 'number' => 1, 'type' => 'standard']]]));
        self::assertResponseStatusCodeSame(201);
    }

    private function createAndPublishEvent(string $venueId, string $title): void
    {
        $this->client->request('GET', sprintf('/api/venues/%s/seats', $venueId));
        $seatId = $this->decodeJson()[0]['id'];

        $this->client->request('POST', '/api/events', [], [], [], $this->encodeJson([
            'title' => $title,
            'description' => 'An amazing test concert event for functional test',
            'date' => '2026-12-01T20:00:00Z',
            'venueId' => $venueId,
            'seats' => [['seatId' => $seatId, 'priceAmount' => 5000]],
        ]));
        self::assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $event = $em->createQueryBuilder()->select('e')->from(Event::class, 'e')->where('e.title = :title')->setParameter('title', $title)->getQuery()->getOneOrNullResult();
        $eventId = $event->id()->toString();

        $this->client->request('POST', sprintf('/api/events/%s/publish', $eventId), [], [], [], '{}');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/events/%s/seats', $eventId));
        $this->eventSeatId = $this->decodeJson()[0]['id'];
    }

    private function uniqueEmail(string $prefix): string
    {
        return sprintf('%s_%s@example.com', $prefix, uniqid());
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function decodeJson(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
