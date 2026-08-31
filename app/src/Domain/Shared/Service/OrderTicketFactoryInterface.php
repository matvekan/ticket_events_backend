<?php

declare(strict_types=1);

namespace App\Domain\Shared\Service;

use App\Domain\Entity\EventSeat;
use App\Domain\Entity\Order;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\IdGeneratorInterface;
use App\Domain\ValueObject\UserId;

interface OrderTicketFactoryInterface
{
    /**
     * @param EventSeat[] $seats
     */
    public function create(
        UserId $userId,
        array $seats,
        ClockInterface $clock,
        IdGeneratorInterface $ids,
    ): Order;
}