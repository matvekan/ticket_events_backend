<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

final class NotEncodableValueExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof NotEncodableValueException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        return new JsonResponse(
            ['error' => 'Invalid request payload.'],
            JsonResponse::HTTP_BAD_REQUEST,
        );
    }
}