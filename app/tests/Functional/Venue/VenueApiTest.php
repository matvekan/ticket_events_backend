<?php

declare(strict_types=1);

namespace App\Tests\Functional\Venue;

use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VenueApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    public function testCreateVenueReturns201WhenAuthenticatedAsManager(): void
    {
        $this->loginAsManager($this->uniqueEmail('venue_mgr'));

        $this->client->request('POST', '/api/venues', [], [], [], $this->encodeJson(['name' => 'Grand Hall', 'address' => '123 Test Street', 'city' => 'Moscow']));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Venue created.', $this->decodeJson()['message']);
    }

    public function testCreateVenueReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('POST', '/api/venues', [], [], [], $this->encodeJson(['name' => 'Grand Hall', 'address' => '123 Test Street', 'city' => 'Moscow']));

        self::assertResponseStatusCodeSame(401);
    }

    public function testCreateVenueReturns403WhenAuthenticatedAsRegularUserWithoutManagerRole(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('venue_user'));

        $this->client->request('POST', '/api/venues', [], [], [], $this->encodeJson(['name' => 'Grand Hall', 'address' => '123 Test Street', 'city' => 'Moscow']));

        self::assertResponseStatusCodeSame(403);
    }

    public function testCreateVenueReturns422WhenRequiredFieldsAreMissing(): void
    {
        $this->loginAsManager($this->uniqueEmail('venue_mgr2'));

        $this->client->request('POST', '/api/venues', [], [], [], $this->encodeJson(['name' => '']));

        self::assertResponseStatusCodeSame(422);
    }

    public function testListVenuesReturns200WhenAuthenticated(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('list_user'));

        $this->client->request('GET', '/api/venues');

        self::assertResponseIsSuccessful();
        self::assertIsArray($this->decodeJson());
    }

    public function testListVenuesReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('GET', '/api/venues');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetVenueReturns200ForExistingVenue(): void
    {
        $managerEmail = $this->uniqueEmail('venue_mgr_get');
        $this->loginAsManager($managerEmail);
        $venueId = $this->createVenueAndExtractId('Hall For Get Test');

        $this->client->request('GET', sprintf('/api/venues/%s', $venueId));

        self::assertResponseIsSuccessful();
        self::assertSame($venueId, $this->decodeJson()['id']);
    }

    public function testGetVenueReturns404WhenVenueDoesNotExist(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('get_404_user'));

        $this->client->request('GET', '/api/venues/00000000-0000-4000-8000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }

    public function testAddSeatsReturns201WhenAuthenticatedAsManager(): void
    {
        $this->loginAsManager($this->uniqueEmail('seats_mgr'));
        $venueId = $this->createVenueAndExtractId('Hall For Seats ' . uniqid());

        $this->client->request('POST', sprintf('/api/venues/%s/seats', $venueId), [], [], [], $this->encodeJson(['seats' => [['row' => 'A', 'number' => 2, 'type' => 'standard']]]));

        self::assertResponseStatusCodeSame(201);
    }

    public function testAddSeatsReturns422WhenPayloadIsInvalid(): void
    {
        $this->loginAsManager($this->uniqueEmail('seats_mgr2'));
        $venueId = $this->createVenueAndExtractId('Hall For Invalid Seats ' . uniqid());

        $this->client->request('POST', sprintf('/api/venues/%s/seats', $venueId), [], [], [], $this->encodeJson(['seats' => [['row' => '', 'number' => 0, 'type' => 'invalid']]]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testAddSeatsReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('POST', '/api/venues/00000000-0000-4000-8000-000000000000/seats', [], [], [], $this->encodeJson(['seats' => [['row' => 'A', 'number' => 1, 'type' => 'standard']]]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetVenueSeatingReturns200WhenAuthenticated(): void
    {
        $this->loginAsManager($this->uniqueEmail('seating_mgr'));
        $venueId = $this->createVenueAndExtractId('Hall For Seating ' . uniqid());
        $this->client->request('POST', sprintf('/api/venues/%s/seats', $venueId), [], [], [], $this->encodeJson(['seats' => [['row' => 'A', 'number' => 3, 'type' => 'standard']]]));
        self::assertResponseStatusCodeSame(201);

        $this->loginAsRegularUser($this->uniqueEmail('seating_user'));
        $this->client->request('GET', sprintf('/api/venues/%s/seats', $venueId));

        self::assertResponseIsSuccessful();
        self::assertIsArray($this->decodeJson());
    }

    private function createVenueAndExtractId(string $name): string
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

        self::fail('Venue not found after creation');

        return '';
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
            $this->promoteToManager($email);
        }

        $path = $manager ? '/api/admin/login' : '/api/auth/login';
        $this->client->request('POST', $path, [], [], [], $this->encodeJson(['email' => $email, 'password' => 'password123']));
        self::assertResponseIsSuccessful();

        $token = $this->decodeJson()['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    private function promoteToManager(string $email): void
    {
        $repo = static::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class);
        $user = $repo->findByEmail(new Email($email));
        $user->changeRoles(['ROLE_ADMIN']);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
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
