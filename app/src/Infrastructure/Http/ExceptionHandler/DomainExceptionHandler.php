<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use App\Domain\Exception\DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DomainExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof DomainException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_BAD_REQUEST,
        );
    }
}
