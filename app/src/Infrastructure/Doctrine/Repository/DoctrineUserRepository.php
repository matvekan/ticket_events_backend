<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class DoctrineUserRepository extends AbstractDoctrineRepository implements UserRepositoryInterface
{
    private const ENTITY = User::class;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function findById(Uuid $id): ?User
    {
        return $this->em()->find(self::ENTITY, $id);
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->em()->getRepository(self::ENTITY)->findOneBy(['email' => $email]);
    }

    public function save(User $user): void
    {
        $this->saveAndFlush($user);
    }
}
