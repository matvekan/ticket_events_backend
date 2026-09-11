<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use App\Domain\Exception\BusinessRuleViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class BusinessRuleViolationExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof BusinessRuleViolationException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => $throwable->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
