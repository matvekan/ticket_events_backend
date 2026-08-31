<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\ChatRoomDto;

/**
 * Read-side port for support chat room listings.
 */
interface ChatRoomReadRepositoryInterface
{
    /** @return ChatRoomDto[] ordered by last activity, descending */
    public function findForSupport(): array;
}
