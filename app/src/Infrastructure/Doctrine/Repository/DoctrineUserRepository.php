<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    /** @var EntityRepository<User> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(User::class);
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager->find(User::class, $id->toString());
    }

    /**
     * @param array<int, UserId> $ids
     * @return array<string, User>
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $stringIds = array_map(static fn (UserId $id): string => $id->toString(), $ids);

        $result = $this->repository
            ->createQueryBuilder('u')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', array_values(array_unique($stringIds)))
            ->getQuery()
            ->getResult();
        assert(is_array($result));
        $users = $result;

        /** @var array<string, User> $byId */
        $byId = [];
        foreach ($users as $user) {
            assert($user instanceof User);
            $byId[$user->rawId()] = $user;
        }

        return $byId;
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findByPasswordResetTokenHash(string $tokenHash): ?User
    {
        return $this->repository->findOneBy(['resetPasswordTokenHash' => $tokenHash]);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
    }
}
