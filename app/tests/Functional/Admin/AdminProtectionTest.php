<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminProtectionTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    public function testAdminRefundReturns403WhenAuthenticatedUserHasNoAdminRole(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('plain'));

        $this->client->request('POST', '/api/admin/orders/00000000-0000-4000-8000-000000000000/refund');

        self::assertResponseStatusCodeSame(403);
        self::assertArrayHasKey('error', $this->decodeJson());
    }

    public function testAdminChatRoomsReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('GET', '/api/admin/chat/rooms');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminChatRoomsReturns200WhenAuthenticatedAsAdmin(): void
    {
        $this->loginAsAdmin($this->uniqueEmail('admin'));

        $this->client->request('GET', '/api/admin/chat/rooms');

        self::assertResponseIsSuccessful();
        self::assertIsArray($this->decodeJson());
    }

    public function testAdminRefundReturns404WhenOrderDoesNotExist(): void
    {
        $this->loginAsAdmin($this->uniqueEmail('admin2'));

        $this->client->request('POST', '/api/admin/orders/00000000-0000-4000-8000-000000000000/refund');

        self::assertResponseStatusCodeSame(404);
        self::assertArrayHasKey('error', $this->decodeJson());
    }

    public function testAdminAnalyticsReturns401WhenNoAuthenticationTokenProvided(): void
    {
        $this->client->request('GET', '/api/admin/analytics');

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminVerifyTicketReturns403WhenUserIsNotAdmin(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('plain2'));

        $this->client->request('GET', '/api/admin/tickets/00000000-0000-4000-8000-000000000000');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminSupportChatRoomsReturns403WhenUserIsNotAdmin(): void
    {
        $this->loginAsRegularUser($this->uniqueEmail('plain3'));

        $this->client->request('GET', '/api/admin/chat/rooms');

        self::assertResponseStatusCodeSame(403);
    }

    private function loginAsRegularUser(string $email): void
    {
        $this->registerAndLogin($email, false);
    }

    private function loginAsAdmin(string $email): void
    {
        $this->registerAndLogin($email, true);
    }

    private function registerAndLogin(string $email, bool $admin): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['name' => 'Test User', 'email' => $email, 'password' => 'password123']));
        self::assertResponseStatusCodeSame(201);

        if ($admin) {
            $this->promoteToAdmin($email);
        }

        $path = $admin ? '/api/admin/login' : '/api/auth/login';
        $this->client->request('POST', $path, [], [], [], $this->encodeJson(['email' => $email, 'password' => 'password123']));
        self::assertResponseIsSuccessful();

        $token = $this->decodeJson()['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    private function promoteToAdmin(string $email): void
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
