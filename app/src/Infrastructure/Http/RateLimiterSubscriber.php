<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.anonymous_api')]
        private RateLimiterFactory $apiLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $ip = $request->getClientIp() ?? 'unknown';
        $limiter = $this->apiLimiter->create($ip);

        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(null, 'Too many requests. Please try again later.');
        }
    }
}
