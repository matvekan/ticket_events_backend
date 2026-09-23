<?php declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Shared;

use App\Infrastructure\Security\DomainUserAdapter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

trait RequiresDomainUserTrait
{
    private function getDomainUser(Security $security): DomainUserAdapter
    {
        $user = $security->getUser();

        if (!$user instanceof DomainUserAdapter) {
            throw new AccessDeniedException('Access denied.');
        }

        return $user;
    }
}
