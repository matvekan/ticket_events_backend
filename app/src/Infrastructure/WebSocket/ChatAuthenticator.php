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
        parse_str($request->getUri()->getQuery(), $query);
        $token = $query['token'] ?? null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($payload) || !isset($payload['username']) || !is_string($payload['username'])) {
            return null;
        }

        return $this->users->findByEmail(new Email($payload['username']));
    }
}