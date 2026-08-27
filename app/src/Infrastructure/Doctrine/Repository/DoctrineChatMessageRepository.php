<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\ChatMessage;
use App\Domain\Repository\ChatMessageRepositoryInterface;
use App\Domain\ValueObject\ChatRoomId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineChatMessageRepository implements ChatMessageRepositoryInterface
{
    /** @var EntityRepository<ChatMessage> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(ChatMessage::class);
    }

    public function findByRoomId(ChatRoomId $roomId): array
    {
        return $this->repository->findBy(['roomId' => $roomId->toString()], ['createdAt' => 'ASC']);
    }

    public function save(ChatMessage $message): void
    {
        $this->entityManager->persist($message);
    }
}
