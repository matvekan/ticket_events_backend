<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class E2ETest extends WebTestCase
{
    private KernelBrowser $client;
    private string $userToken;
    private string $venueId;
    private string $venueName;
    private string $seatId;
    private string $eventId;
    private string $orderId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
        $this->venueName = 'Test Hall ' . uniqid();
    }

    public function testReservePayCancelRefundFlow(): void
    {
        $suffix = uniqid();
        $email = sprintf('flow1_%s@test.com', $suffix);

        $this->registerUser($email);
        $this->login($email);
        $this->makeAdmin($email);
        $this->createVenue();
        $this->setVenueId();
        $this->addSeats();
        $this->createEvent(sprintf('Test Concert %s', $suffix), 'An amazing test concert event for integration');
        $this->publishEvent();
        $this->listEvents();
        $this->getAvailableSeats();
        $this->reserveSeats();
        $this->getOrder('pending');
        $this->payOrder();
        $this->getOrder('paid');
        $this->cancelOrder();
        $this->getOrder('refunded');
    }

    public function testCancelBeforePay(): void
    {
        $suffix = uniqid();
        $email = sprintf('flow2_%s@test.com', $suffix);

        $this->registerUser($email);
        $this->login($email);
        $this->makeAdmin($email);
        $this->createEvent(sprintf('Cancel Before Pay %s', $suffix), 'Cancel while still pending');
        $this->publishEvent();
        $this->getAvailableSeats();
        $this->reserveSeats();
        $this->getOrder('pending');
        $this->cancelOrder();
        $this->getOrder('cancelled');
    }

    public function testChatSupportFlow(): void
    {
        $suffix = uniqid();
        $userEmail = sprintf('chatuser_%s@test.com', $suffix);
        $adminEmail = sprintf('chatadmin_%s@test.com', $suffix);
        $strangerEmail = sprintf('chatstranger_%s@test.com', $suffix);

        $this->registerUser($userEmail);
        $this->registerUser($adminEmail);
        $this->registerUser($strangerEmail);
        $this->login($adminEmail);
        $this->makeAdmin($adminEmail);

        // User opens a room (get-or-create is idempotent)
        $this->login($userEmail);
        $this->client->request('POST', '/api/chat/room', [], [], [], '{}');
        self::assertResponseStatusCodeSame(201);
        $room = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $room);

        $this->client->request('POST', '/api/chat/room', [], [], [], '{}');
        self::assertSame($room['id'], json_decode($this->client->getResponse()->getContent(), true)['id']);

        // User sends a message (application service, same path as the WS handler)
        $user = self::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class)
            ->findByEmail(new \App\Domain\ValueObject\Email($userEmail));
        $chatService = self::getContainer()->get(\App\Application\Service\Chat\ChatService::class);
        $chatService->sendMessage(new \Symfony\Component\Uid\Uuid($room['id']), $user->id(), 'Privet, podderzhka!');

        // History shows the user message
        $this->client->request('GET', sprintf('/api/chat/rooms/%s/messages', $room['id']));
        self::assertResponseIsSuccessful();
        $messages = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $messages);
        self::assertFalse($messages[0]['isSupport']);

        // Support replies
        $admin = self::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class)
            ->findByEmail(new \App\Domain\ValueObject\Email($adminEmail));
        $chatService->sendMessage(new \Symfony\Component\Uid\Uuid($room['id']), $admin->id(), 'Chem mogu pomoch?');

        // Support room list contains the room with last message preview
        $this->login($adminEmail);
        $this->client->request('GET', '/api/admin/chat/rooms');
        self::assertResponseIsSuccessful();
        $rooms = json_decode($this->client->getResponse()->getContent(), true);
        $foundRoom = null;
        foreach ($rooms as $candidate) {
            if ($candidate['id'] === $room['id']) {
                $foundRoom = $candidate;
                break;
            }
        }
        self::assertNotNull($foundRoom, 'Created room should be listed for support');
        self::assertSame('Chem mogu pomoch?', $foundRoom['lastMessage']['text']);
        self::assertTrue($foundRoom['lastMessage']['isSupport']);

        // Stranger cannot read the conversation
        $this->login($strangerEmail);
        $this->client->request('GET', sprintf('/api/chat/rooms/%s/messages', $room['id']));
        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminRefundFlow(): void
    {
        $suffix = uniqid();
        $userEmail = sprintf('customer_%s@test.com', $suffix);
        $adminEmail = sprintf('admin_%s@test.com', $suffix);

        // Customer: register, login, create venue/event, reserve, pay
        $this->registerUser($userEmail);
        $this->login($userEmail);
        $this->makeAdmin($userEmail);
        $this->createVenue();
        $this->setVenueId();
        $this->addSeats();
        $this->createEvent(sprintf('Refund Test Event %s', $suffix), 'Event for admin refund test');
        $this->publishEvent();
        $this->getAvailableSeats();
        $this->reserveSeats();
        $this->getOrder('pending');
        $this->payOrder();
        $this->getOrder('paid');

        // Admin: register, get ROLE_ADMIN, login, refund order
        $this->registerUser($adminEmail);
        $this->makeAdmin($adminEmail);
        $this->login($adminEmail);
        $this->client->request('POST', sprintf('/api/admin/orders/%s/refund', $this->orderId));

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Order refunded.', $data['message']);

        // Verify order is refunded
        $this->login($userEmail);
        $this->getOrder('refunded');
    }

    private function registerUser(string $email): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]));

        if ($this->client->getResponse()->getStatusCode() !== 201) {
            throw new \RuntimeException('Failed to register user: ' . $this->client->getResponse()->getContent());
        }
    }

    private function login(string $email): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => $email,
            'password' => 'password123',
        ]));

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);

        $this->userToken = $data['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $this->userToken));
    }

    private function makeAdmin(string $email): void
    {
        $userRepository = self::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class);
        $user = $userRepository->findByEmail(new \App\Domain\ValueObject\Email($email));

        if (!$user) {
            throw new \RuntimeException(sprintf('User with email "%s" not found', $email));
        }

        $user->updateRoles(['ROLE_ADMIN']);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function createVenue(): void
    {
        $this->client->request('POST', '/api/venues', [], [], [], json_encode([
            'name' => $this->venueName,
            'address' => '123 Test Street',
            'city' => 'Moscow',
        ]));

        self::assertResponseStatusCodeSame(201);
    }

    private function setVenueId(): void
    {
        $this->client->request('GET', '/api/venues');

        self::assertResponseIsSuccessful();
        $venues = json_decode($this->client->getResponse()->getContent(), true);

        foreach ($venues as $venue) {
            if ($venue['name'] === $this->venueName) {
                $this->venueId = $venue['id'];
                return;
            }
        }

        throw new \RuntimeException('Venue not found after creation');
    }

    private function addSeats(): void
    {
        $this->client->request('POST', sprintf('/api/venues/%s/seats', $this->venueId), [], [], [], json_encode([
            'seats' => [
                ['row' => 'A', 'number' => 1, 'type' => 'standard', 'sector' => 'Orchestra'],
            ],
        ]));

        self::assertResponseStatusCodeSame(201);
    }

    private function createEvent(string $title, string $description): void
    {
        if (!isset($this->venueId)) {
            $this->createVenue();
            $this->setVenueId();
            $this->addSeats();
        }

        $this->client->request('GET', sprintf('/api/venues/%s/seats', $this->venueId));

        self::assertResponseIsSuccessful();
        $seats = json_decode($this->client->getResponse()->getContent(), true);
        $this->seatId = $seats[0]['id'];

        $this->client->request('POST', '/api/events', [], [], [], json_encode([
            'title' => $title,
            'description' => $description,
            'date' => '2026-12-01T20:00:00Z',
            'venueId' => $this->venueId,
            'seats' => [
                ['seatId' => $this->seatId, 'priceAmount' => 5000],
            ],
        ]));

        self::assertResponseStatusCodeSame(201);

        $this->eventId = $this->findEventId($title);
    }

    private function findEventId(string $title): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $event = $em->createQueryBuilder()
            ->select('e')
            ->from(\App\Domain\Entity\Event::class, 'e')
            ->where('e.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$event) {
            throw new \RuntimeException(sprintf('Event "%s" not found in database', $title));
        }

        return $event->id()->toRfc4122();
    }

    private function publishEvent(): void
    {
        $this->client->request('POST', sprintf('/api/events/%s/publish', $this->eventId), [], [], [], '{}');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Event published.', $data['message']);
    }

    private function listEvents(): void
    {
        // Public detail endpoint: deterministic check that the published event is visible,
        // unlike the paginated list (ties on identical dates may fall outside the first page).
        $this->client->request('GET', sprintf('/api/events/%s', $this->eventId));

        self::assertResponseIsSuccessful();
        $event = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('published', $event['status']);
    }

    private function getAvailableSeats(): void
    {
        $this->client->request('GET', sprintf('/api/events/%s/seats', $this->eventId));

        self::assertResponseIsSuccessful();
        $seats = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNotEmpty($seats);

        $this->seatId = $seats[0]['id'];
        self::assertSame('free', $seats[0]['status']);
    }

    private function reserveSeats(): void
    {
        $this->client->request('POST', '/api/orders', [], [], [], json_encode([
            'seatIds' => [$this->seatId],
        ]));

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Seats reserved.', $data['message']);
    }

    private function getOrder(string $expectedStatus): void
    {
        $this->client->request('GET', '/api/orders/my');

        self::assertResponseIsSuccessful();
        $orders = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNotEmpty($orders);

        $this->orderId = $orders[0]['id'];
        self::assertSame($expectedStatus, $orders[0]['status']);
    }

    private function payOrder(): void
    {
        $this->client->request('POST', sprintf('/api/orders/%s/pay', $this->orderId), [], [], [], '{}');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('paymentUrl', $data);
        self::assertStringStartsWith('/mock-bank/', $data['paymentUrl']);

        $this->client->request('POST', sprintf('%s/charge', $data['paymentUrl']));
        self::assertResponseRedirects();

        $this->client->request('GET', sprintf('/api/orders/%s', $this->orderId), [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->userToken]);
        self::assertResponseIsSuccessful();
        $order = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('paid', $order['status']);
    }

    private function cancelOrder(): void
    {
        $this->client->request('POST', sprintf('/api/orders/%s/cancel', $this->orderId));

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Order cancelled.', $data['message']);
    }
}
