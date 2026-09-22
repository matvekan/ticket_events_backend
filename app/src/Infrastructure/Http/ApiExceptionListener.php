<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Http\ExceptionHandler\CompositeExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class ApiExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly CompositeExceptionHandler $exceptionHandler,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $throwable = $this->unwrap($event->getThrowable());

        if ($this->exceptionHandler->supports($throwable)) {
            $event->setResponse($this->exceptionHandler->handle($throwable));
        }
    }

    private function unwrap(\Throwable $throwable): \Throwable
    {
        if ($throwable instanceof HandlerFailedException && $throwable->getPrevious() instanceof \Throwable) {
            return $throwable->getPrevious();
        }

        return $throwable;
    }
}
