<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Exception\InvalidArgumentException;

final class InvalidIdentifierExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof InvalidArgumentException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Invalid identifier.'],
            JsonResponse::HTTP_BAD_REQUEST,
        );
    }
}
