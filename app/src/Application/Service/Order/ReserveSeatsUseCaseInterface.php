<?php

declare(strict_types=1);

namespace App\Application\Service\Order;

use App\Domain\ValueObject\UserId;

interface ReserveSeatsUseCaseInterface
{
    /**
     * @param array<int, string> $seatIds
     */
    public function execute(UserId $userId, array $seatIds): void;
}
