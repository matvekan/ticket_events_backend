<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Presentation layer: AuthController — регистрация и выдача JWT.
 */
final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    public function testRegisterReturns201(): void
    {
        // Arrange
        $email = sprintf('reg_%s@example.com', uniqid());

        // Act
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]));

        // Assert
        self::assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('User registered successfully.', $data['message']);
    }

    public function testRegisterReturns422OnValidationError(): void
    {
        // Arrange (невалидный email + короткий пароль)
        $payload = json_encode(['name' => 'A', 'email' => 'not-an-email', 'password' => '123']);

        // Act
        $this->client->request('POST', '/api/auth/register', [], [], [], $payload);

        // Assert (ApiExceptionListener → JSON 422)
        self::assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $data);
    }

    public function testLoginReturnsJwtToken(): void
    {
        // Arrange
        $email = sprintf('login_%s@example.com', uniqid());
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]));
        self::assertResponseStatusCodeSame(201);

        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => $email,
            'password' => 'password123',
        ]));

        // Assert
        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testLoginReturns401OnBadCredentials(): void
    {
        // Arrange
        $email = sprintf('bad_%s@example.com', uniqid());
        $this->client->request('POST', '/api/auth/register', [], [], [], json_encode([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]));

        // Act
        $this->client->request('POST', '/api/auth/login', [], [], [], json_encode([
            'email' => $email,
            'password' => 'wrong-password',
        ]));

        // Assert
        self::assertResponseStatusCodeSame(401);
    }
}
