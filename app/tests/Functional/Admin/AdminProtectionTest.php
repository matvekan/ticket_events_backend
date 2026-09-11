<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Presentation layer: защита /api/admin/... (403 для обычных пользователей, 401 без токена).
 */
final class AdminProtectionTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    public function testAdminRefundRequiresAdminRole(): void
    {
        // Arrange — обычный пользователь без ROLE_ADMIN
        $email = sprintf('plain_%s@example.com', uniqid());
        $this->loginAs($email, false);

        // Act
        $this->client->request('POST', '/api/admin/orders/00000000-0000-4000-8000-000000000000/refund');

        // Assert
        self::assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testAdminRoutesRequireAuthentication(): void
    {
        // Arrange — анонимный клиент
        // Act
        $this->client->request('GET', '/api/admin/chat/rooms');

        // Assert
        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminChatRoomsListIsAccessibleForAdmin(): void
    {
        // Arrange — админ
        $email = sprintf('admin_%s@example.com', uniqid());
        $this->loginAs($email, true);

        // Act
        $this->client->request('GET', '/api/admin/chat/rooms');

        // Assert
        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testAdminRefundUnknownOrderReturns404(): void
    {
        // Arrange — админ
        $email = sprintf('admin2_%s@example.com', uniqid());
        $this->loginAs($email, true);

        // Act
        $this->client->request('POST', '/api/admin/orders/00000000-0000-4000-8000-000000000000/refund');

        // Assert (ApiExceptionListener маппит EntityNotFound → 404 JSON)
        self::assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    private function loginAs(string $email, bool $admin): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'name' => 'Test User', 'email' => $email, 'password' => 'password123',
        ]));
        self::assertResponseStatusCodeSame(201);

        if ($admin) {
            $users = static::getContainer()->get(\App\Domain\Repository\UserRepositoryInterface::class);
            $user = $users->findByEmail(new Email($email));
            $user->changeRoles(['ROLE_ADMIN']);
            static::getContainer()->get(EntityManagerInterface::class)->flush();
        }

        $loginPath = $admin ? '/api/admin/login' : '/api/auth/login';
        $this->client->request('POST', $loginPath, [], [], [], json_encode([
            'email' => $email, 'password' => 'password123',
        ]));
        self::assertResponseIsSuccessful();
        $token = json_decode($this->client->getResponse()->getContent(), true)['token'];
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }
}
