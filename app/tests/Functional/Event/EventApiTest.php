<?php

declare(strict_types=1);

namespace App\Tests\Functional\Event;

use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EventApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    public function testCreateEventReturns201WhenAuthenticatedAsManager(): void
    {
        $venueId = $this->prepareVenueWithSeat();

        $this->client->request('POST', '/api/events', [], [], [], $this->encodeJson($this->validEventPayload($venueId, 'Manager Event')));

        self::assertResponseStatusCodeSame(201);
    }

    public function testCreateEventReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('POST', '/api/events', [], [], [], $this->encodeJson(['title' => 'Test', 'description' => 'Desc', 'date' => '2026-12-01T20:00:00Z', 'venueId' => '00000000-0000-4000-8000-000000000000', 'seats' => []]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateEventReturns403WhenAuthenticatedAsRegularUser(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('event_user'));
        $this->client->request('POST', '/api/events', [], [], [], $this->encodeJson(['title' => 'Test', 'description' => 'Desc', 'date' => '2026-12-01T20:00:00Z', 'venueId' => '00000000-0000-4000-8000-000000000000', 'seats' => []]));

        self::assertResponseStatusCodeSame(403);
    }

    public function testCreateEventReturns422WhenRequiredFieldsAreMissing(): void
    {
        $this->loginAsManager($this->uniqueEmail('event_mgr_422'));
        $venueId = $this->createVenue('Hall for 422 Event ' . uniqid());
        $this->addSeatToVenue($venueId);

        $this->client->request('POST', '/api/events', [], [], [], $this->encodeJson(['title' => 'AB']));

        self::assertResponseStatusCodeSame(422);
    }

    public function testListEventsReturns200AndIsPubliclyAccessible(): void
    {
        $this->client->request('GET', '/api/events');

        self::assertResponseIsSuccessful();
        self::assertIsArray($this->decodeJson());
    }

    public function testGetEventReturns200ForExistingEvent(): void
    {
        $venueId = $this->prepareVenueWithSeat();
        $eventId = $this->createEvent($venueId, 'Get Event ' . uniqid());

        $this->client->request('GET', sprintf('/api/events/%s', $eventId));

        self::assertResponseIsSuccessful();
        self::assertSame($eventId, $this->decodeJson()['id']);
    }

    public function testGetEventReturns404WhenEventDoesNotExist(): void
    {
        $this->client->request('GET', '/api/events/00000000-0000-4000-8000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPublishEventReturns200WhenAuthenticatedAsManager(): void
    {
        $venueId = $this->prepareVenueWithSeat();
        $eventId = $this->createEvent($venueId, 'Publish Event ' . uniqid());

        $this->client->request('POST', sprintf('/api/events/%s/publish', $eventId), [], [], [], '{}');

        self::assertResponseIsSuccessful();
    }

    public function testPublishEventReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('POST', '/api/events/00000000-0000-4000-8000-000000000000/publish', [], [], [], '{}');

        self::assertResponseStatusCodeSame(401);
    }

    public function testPublishEventReturns404WhenEventDoesNotExist(): void
    {
        $this->loginAsManager($this->uniqueEmail('publish_404_mgr'));
        $this->client->request('POST', '/api/events/00000000-0000-4000-8000-000000000000/publish', [], [], [], '{}');

        self::assertResponseStatusCodeSame(404);
    }

    public function testCancelEventReturns200WhenAuthenticatedAsManager(): void
    {
        $venueId = $this->prepareVenueWithSeat();
        $eventId = $this->createEvent($venueId, 'Cancel Event ' . uniqid());

        $this->client->request('POST', sprintf('/api/events/%s/cancel', $eventId), [], [], [], '{}');

        self::assertResponseIsSuccessful();
    }

    public function testCancelEventReturns403WhenAuthenticatedAsRegularUser(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('cancel_user'));
        $this->client->request('POST', '/api/events/00000000-0000-4000-8000-000000000000/cancel', [], [], [], '{}');

        self::assertResponseStatusCodeSame(403);
    }

    public function testGetAvailableSeatsReturns200ForExistingEvent(): void
    {
        $venueId = $this->prepareVenueWithSeat();
        $eventId = $this->createEvent($venueId, 'Seats Event ' . uniqid());
        $this->client->request('POST', sprintf('/api/events/%s/publish', $eventId), [], [], [], '{}');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', sprintf('/api/events/%s/seats', $eventId));

        self::assertResponseIsSuccessful();
        self::assertIsArray($this->decodeJson());
    }

    public function testGetAvailableSeatsReturns200WithEmptyListWhenEventDoesNotExist(): void
    {
        $this->client->request('GET', '/api/events/00000000-0000-4000-8000-000000000000/seats');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->decodeJson());
    }

    public function testSearchEventsReturns200WithQueryParameter(): void
    {
        $this->client->request('GET', '/api/events/search?q=Concert');

        self::assertResponseIsSuccessful();
        self::assertIsArray($this->decodeJson());
    }

    private function prepareVenueWithSeat(): string
    {
        $managerEmail = $this->uniqueEmail('event_mgr');
        $this->loginAsManager($managerEmail);
        $venueId = $this->createVenue('Event Hall ' . uniqid());
        $this->addSeatToVenue($venueId);

        return $venueId;
    }

    private function createEvent(string $venueId, string $title): string
    {
        $seatId = $this->fetchSeatId($venueId);
        $this->client->request('POST', '/api/events', [], [], [], $this->encodeJson([
            'title' => $title,
            'description' => 'An amazing test concert event for functional test suite',
            'date' => '2026-12-01T20:00:00Z',
            'venueId' => $venueId,
            'seats' => [['seatId' => $seatId, 'priceAmount' => 5000]],
        ]));
        self::assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $event = $em->createQueryBuilder()->select('e')->from(\App\Domain\Entity\Event::class, 'e')->where('e.title = :title')->setParameter('title', $title)->getQuery()->getOneOrNullResult();
        self::assertNotNull($event);

        return $event->id()->toString();
    }

    private function fetchSeatId(string $venueId): string
    {
        $this->client->request('GET', sprintf('/api/venues/%s/seats', $venueId));

        return $this->decodeJson()[0]['id'];
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

    private function addSeatToVenue(string $venueId): void
    {
        $this->client->request('POST', sprintf('/api/venues/%s/seats', $venueId), [], [], [], $this->encodeJson(['seats' => [['row' => 'A', 'number' => 1, 'type' => 'standard']]]));
        self::assertResponseStatusCodeSame(201);
    }

    private function validEventPayload(string $venueId, string $title): array
    {
        $seatId = $this->fetchSeatId($venueId);

        return [
            'title' => $title,
            'description' => 'An amazing test concert event for functional test suite',
            'date' => '2026-12-01T20:00:00Z',
            'venueId' => $venueId,
            'seats' => [['seatId' => $seatId, 'priceAmount' => 5000]],
        ];
    }

    private function loginAsManager(string $email): void
    {
        $this->registerAndLogin($email, true);
    }

    private function loginAsRegularUser(string $email): void
    {
        $this->registerAndLogin($email, false);
    }

    private function registerAndLogin(string $email, bool $manager): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['name' => 'Test User', 'email' => $email, 'password' => 'password123']));
        self::assertResponseStatusCodeSame(201);

        if ($manager) {
            $repo = static::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class);
            $user = $repo->findByEmail(new Email($email));
            $user->changeRoles(['ROLE_ADMIN']);
            static::getContainer()->get(EntityManagerInterface::class)->flush();
        }

        $path = $manager ? '/api/admin/login' : '/api/auth/login';
        $this->client->request('POST', $path, [], [], [], $this->encodeJson(['email' => $email, 'password' => 'password123']));
        self::assertResponseIsSuccessful();

        $token = $this->decodeJson()['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
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
