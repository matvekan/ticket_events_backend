<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class OptimisticLockExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof OptimisticLockException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Resource was modified concurrently. Please retry.'],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
