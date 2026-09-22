<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->setServerParameter('CONTENT_TYPE', 'application/json');
    }

    public function testRegisterReturns201WhenPayloadIsValid(): void
    {
        $response = $this->registerUser($this->uniqueEmail('reg'), 'Test User', 'password123');

        self::assertResponseStatusCodeSame(201);
        self::assertSame('User registered successfully.', $this->decodeJson($response)['message']);
    }

    public function testRegisterReturns422WhenEmailIsInvalidAndNameIsTooShort(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['name' => 'A', 'email' => 'not-an-email', 'password' => '123']));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('error', $this->decodeJson($this->client->getResponse()));
    }

    public function testRegisterReturns422WhenRequiredFieldsAreMissing(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['email' => 'test@example.com']));

        self::assertResponseStatusCodeSame(422);
    }

    public function testLoginReturns200AndJwtTokenAfterSuccessfulRegistration(): void
    {
        $email = $this->uniqueEmail('login');
        $this->registerUser($email, 'Test User', 'password123');

        $this->client->request('POST', '/api/auth/login', [], [], [], $this->encodeJson(['email' => $email, 'password' => 'password123']));

        self::assertResponseIsSuccessful();
        $data = $this->decodeJson($this->client->getResponse());
        self::assertArrayHasKey('token', $data);
        self::assertNotEmpty($data['token']);
    }

    public function testLoginReturns401WhenPasswordIsIncorrect(): void
    {
        $email = $this->uniqueEmail('bad');
        $this->registerUser($email, 'Test User', 'password123');

        $this->client->request('POST', '/api/auth/login', [], [], [], $this->encodeJson(['email' => $email, 'password' => 'wrong-password']));

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginReturns401WhenUserDoesNotExist(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [], $this->encodeJson(['email' => 'unknown@example.com', 'password' => 'password123']));

        self::assertResponseStatusCodeSame(401);
    }

    public function testForgotPasswordReturns200ForExistingEmail(): void
    {
        $email = $this->uniqueEmail('forgot');
        $this->registerUser($email, 'Test User', 'password123');

        $this->client->request('POST', '/api/auth/forgot-password', [], [], [], $this->encodeJson(['email' => $email]));

        self::assertResponseIsSuccessful();
    }

    public function testForgotPasswordReturns422WhenEmailIsInvalid(): void
    {
        $this->client->request('POST', '/api/auth/forgot-password', [], [], [], $this->encodeJson(['email' => 'not-an-email']));

        self::assertResponseStatusCodeSame(422);
    }

    private function registerUser(string $email, string $name, string $password): \Symfony\Component\HttpFoundation\Response
    {
        $this->client->request('POST', '/api/auth/register', [], [], [], $this->encodeJson(['name' => $name, 'email' => $email, 'password' => $password]));

        return $this->client->getResponse();
    }

    private function uniqueEmail(string $prefix): string
    {
        return sprintf('%s_%s@example.com', $prefix, uniqid());
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    private function decodeJson(\Symfony\Component\HttpFoundation\Response $response): array
    {
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
