<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use App\Application\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApplicationAccessDeniedExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof AccessDeniedException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_FORBIDDEN,
        );
    }
}
