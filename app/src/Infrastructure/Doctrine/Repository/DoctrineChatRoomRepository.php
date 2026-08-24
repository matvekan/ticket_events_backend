<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\ChatRoom;
use App\Domain\Repository\ChatRoomRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineChatRoomRepository implements ChatRoomRepositoryInterface
{
    /** @var EntityRepository<ChatRoom> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(ChatRoom::class);
    }

    public function findById(ChatRoomId $id): ?ChatRoom
    {
        return $this->entityManager->find(ChatRoom::class, $id->toString());
    }

    public function findByUserId(UserId $userId): ?ChatRoom
    {
        return $this->repository->findOneBy(['user' => $userId->toString()]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function save(ChatRoom $room): void
    {
        $this->entityManager->persist($room);
    }
}
