<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Exception\AccessDeniedException as ApplicationAccessDeniedException;
use App\Application\Exception\EntityNotFoundException as ApplicationEntityNotFoundException;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\EntityNotFoundException;
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
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Translates exceptions thrown by the Application/Domain layers into
 * consistent JSON error responses for /api routes.
 *
 * HTTP status mapping lives here (Infrastructure) — domain and application
 * exceptions carry no transport semantics.
 */
final class ApiExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();
        if (!str_starts_with($path, '/api') && !str_starts_with($path, '/mock-bank')) {
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
            // Security component denials (firewall/access_control).
            $throwable instanceof AccessDeniedException => new JsonResponse(
                ['error' => 'Access denied.'],
                JsonResponse::HTTP_FORBIDDEN,
            ),

            // Application-level authorization denials.
            $throwable instanceof ApplicationAccessDeniedException => new JsonResponse(
                ['error' => $throwable->getMessage()],
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

            // Domain exceptions carry no status codes — the transport decides.
            $throwable instanceof EntityNotFoundException => new JsonResponse(
                ['error' => $throwable->getMessage()],
                JsonResponse::HTTP_NOT_FOUND,
            ),

            $throwable instanceof BusinessRuleViolationException => new JsonResponse(
                ['error' => $throwable->getMessage()],
                JsonResponse::HTTP_CONFLICT,
            ),

            $throwable instanceof ApplicationEntityNotFoundException => new JsonResponse(
                ['error' => $throwable->getMessage()],
                JsonResponse::HTTP_NOT_FOUND,
            ),

            $throwable instanceof OptimisticLockException => new JsonResponse(
                ['error' => 'Resource was modified concurrently. Please retry.'],
                JsonResponse::HTTP_CONFLICT,
            ),

            $throwable instanceof DomainException => new JsonResponse(
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
