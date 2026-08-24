<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Exception\DomainException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException as MessengerValidationFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Uid\Exception\InvalidArgumentException as InvalidIdentifierException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Converts exceptions thrown by the Application/Domain layers
 * into consistent JSON error responses for /api routes.
 */
final class ApiExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $this->unwrap($event->getThrowable());

        $response = $this->resolveResponse($throwable);
        if ($response !== null) {
            $event->setResponse($response);
        }
    }

    private function unwrap(\Throwable $throwable): \Throwable
    {
        if ($throwable instanceof HandlerFailedException && $throwable->getPrevious() instanceof \Throwable) {
            return $throwable->getPrevious();
        }

        return $throwable;
    }

    private function resolveResponse(\Throwable $throwable): ?JsonResponse
    {
        return match (true) {
            $throwable instanceof AccessDeniedException => new JsonResponse(
                ['error' => 'Access denied.'],
                JsonResponse::HTTP_FORBIDDEN,
            ),

            $throwable instanceof InvalidIdentifierException => new JsonResponse(
                ['error' => 'Invalid identifier.'],
                JsonResponse::HTTP_BAD_REQUEST,
            ),

            $throwable instanceof ValidationFailedException, $throwable instanceof MessengerValidationFailedException => $this->validationResponse($throwable),

            $throwable instanceof NotEncodableValueException => new JsonResponse(
                ['error' => 'Invalid request payload.'],
                JsonResponse::HTTP_BAD_REQUEST,
            ),

            $throwable instanceof HttpExceptionInterface => new JsonResponse(
                ['error' => $throwable->getMessage()],
                $throwable->getStatusCode(),
            ),

            $throwable instanceof DomainException => new JsonResponse(
                ['error' => $throwable->getMessage()],
                $throwable->statusCode(),
            ),

            $throwable instanceof \DomainException => new JsonResponse(
                ['error' => $throwable->getMessage()],
                JsonResponse::HTTP_BAD_REQUEST,
            ),

            default => null,
        };
    }

    private function validationResponse(ValidationFailedException|MessengerValidationFailedException $exception): JsonResponse
    {
        $violations = [];

        foreach ($exception->getViolations() as $violation) {
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
