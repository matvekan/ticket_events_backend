<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class HttpExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof HttpExceptionInterface;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        assert($throwable instanceof HttpExceptionInterface);

        return new JsonResponse(
            ['error' => $throwable->getMessage()],
            $throwable->getStatusCode(),
        );
    }
}
