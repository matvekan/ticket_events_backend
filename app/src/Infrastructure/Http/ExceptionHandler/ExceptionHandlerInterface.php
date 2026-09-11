<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;

interface ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool;

    public function handle(\Throwable $throwable): JsonResponse;
}
