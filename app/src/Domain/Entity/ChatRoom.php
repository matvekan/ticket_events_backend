<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class ChatRoom
{
    private Uuid $id;
    private User $user;
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, ChatMessage> */
    private Collection $messages;

    private function __construct(User $user)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->createdAt = new \DateTimeImmutable();
        $this->messages = new ArrayCollection();
    }

    public static function create(User $user): self
    {
        return new self($user);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, ChatMessage> */
    public function messages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatMessage $message): void
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
        }
    }
}