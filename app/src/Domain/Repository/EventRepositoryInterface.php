<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Event;
use Symfony\Component\Uid\Uuid;

interface EventRepositoryInterface
{
    public function findById(Uuid $id): ?Event;

    /** @return Event[] */
    public function findAllPublished(): array;

    public function save(Event $event): void;
}
