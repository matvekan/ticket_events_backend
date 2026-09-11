<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class AdminLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $user = $event->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $path = $request->getPathInfo();

        if ($path === '/api/admin/login' && ! $isAdmin) {
            throw new AccessDeniedException('Access denied. Admin role required.');
        }

        if ($path === '/api/auth/login' && $isAdmin) {
            throw new AccessDeniedException('Admins must use the admin login page.');
        }
    }
}
