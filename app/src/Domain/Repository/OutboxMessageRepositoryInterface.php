<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Infrastructure\Outbox\OutboxMessage;

interface OutboxMessageRepositoryInterface
{

    public function findPending(int $limit): array;

    public function save(OutboxMessage $message): void;

    public function flush(): void;
}
