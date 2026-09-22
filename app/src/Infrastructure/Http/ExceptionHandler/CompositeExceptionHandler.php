<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\ExceptionHandler;

use Symfony\Component\HttpFoundation\JsonResponse;

final class CompositeExceptionHandler implements ExceptionHandlerInterface
{
    /** @var array<int, ExceptionHandlerInterface> */
    private array $handlers = [];

    public function addHandler(ExceptionHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function supports(\Throwable $throwable): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($throwable)) {
                return true;
            }
        }

        return false;
    }

    public function handle(\Throwable $throwable): JsonResponse
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($throwable)) {
                return $handler->handle($throwable);
            }
        }

        throw new \LogicException('No handler found for exception: '.$throwable::class);
    }
}
