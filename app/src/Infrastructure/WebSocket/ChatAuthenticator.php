<?php

declare(strict_types=1);

namespace App\Infrastructure\WebSocket;

use Amp\Http\Server\Request;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

final class ChatAuthenticator
{
    public function __construct(
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function authenticate(Request $request): ?User
    {
        $token = $this->extractToken($request);

        if (!\is_string($token) || $token === '') {
            return null;
        }

        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Throwable) {
            return null;
        }

        if (!isset($payload['username']) || !\is_string($payload['username'])) {
            return null;
        }

        return $this->users->findByEmail(new Email($payload['username']));
    }

    private function extractToken(Request $request): ?string
    {
        $protocolHeader = $request->getHeader('sec-websocket-protocol');
        if (\is_string($protocolHeader) && $protocolHeader !== '' && !str_contains($protocolHeader, ',')) {
            return trim($protocolHeader);
        }

        parse_str($request->getUri()->getQuery(), $query);
        $token = $query['token'] ?? null;

        return \is_string($token) ? $token : null;
    }
}
