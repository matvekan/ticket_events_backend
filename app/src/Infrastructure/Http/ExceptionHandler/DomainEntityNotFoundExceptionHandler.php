<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use App\Domain\Exception\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DomainEntityNotFoundExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof EntityNotFoundException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_NOT_FOUND,
        );
    }
}