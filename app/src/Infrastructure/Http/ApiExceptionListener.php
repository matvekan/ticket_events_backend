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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidationFailedException as ValidatorValidationFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException as MessengerValidationFailedException;

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

        $throwable = $event->getThrowable();

        if ($throwable instanceof HandlerFailedException) {
            $previous = $throwable->getPrevious();
            if ($previous instanceof \Throwable) {
                $throwable = $previous;
            }
        }

        if ($throwable instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Access denied.'],
                JsonResponse::HTTP_FORBIDDEN,
            ));

            return;
        }

        if ($throwable instanceof InvalidArgumentException) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Invalid identifier.'],
                JsonResponse::HTTP_BAD_REQUEST,
            ));

            return;
        }

        if ($throwable instanceof ValidatorValidationFailedException
            || $throwable instanceof MessengerValidationFailedException) {
            $errors = [];
            foreach ($throwable->getViolations() as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse(
                ['error' => 'Validation failed.', 'violations' => $errors],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            ));

            return;
        }

        if ($throwable instanceof NotEncodableValueException) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Invalid request payload.'],
                JsonResponse::HTTP_BAD_REQUEST,
            ));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $throwable->getMessage()],
                $throwable->getStatusCode(),
            ));

            return;
        }

        if (!$throwable instanceof DomainException && !$throwable instanceof \DomainException) {
            return;
        }

        $statusCode = $throwable instanceof DomainException
            ? $throwable->statusCode()
            : JsonResponse::HTTP_BAD_REQUEST;

        $event->setResponse(new JsonResponse(
            ['error' => $throwable->getMessage()],
            $statusCode,
        ));
    }
}