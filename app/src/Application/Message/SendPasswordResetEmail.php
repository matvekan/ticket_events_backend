<?php

declare(strict_types=1);

namespace App\Application\Message;

final readonly class SendPasswordResetEmail
{
    public function __construct(
        public string $email,
        public string $token,
    ) {
    }
}
