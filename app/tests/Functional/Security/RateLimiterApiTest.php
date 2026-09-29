<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RateLimiterApiTest extends WebTestCase
{
    public function testAnonymousApiRateLimitIsEnforced(): void
    {
        $client = static::createClient();
        for ($i = 0; $i < 100; ++$i) {
            $client->request('GET', '/api/events');
            self::assertResponseIsSuccessful();
        }

        $client->request('GET', '/api/events');

        self::assertResponseStatusCodeSame(429);
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertStringContainsString('Too many requests', $response['error'] ?? '');
    }
}
