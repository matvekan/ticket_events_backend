<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AccessDeniedExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof AccessDeniedException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Access denied.'],
            JsonResponse::HTTP_FORBIDDEN,
        );
    }
}