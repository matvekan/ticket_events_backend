<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException as MessengerValidationFailedException;

final class ValidationExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof ValidationFailedException
            || $throwable instanceof MessengerValidationFailedException;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        $violations = [];

        foreach ($throwable->getViolations() as $violation) {
            $violations[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return new JsonResponse(
            ['error' => 'Validation failed.', 'violations' => $violations],
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}