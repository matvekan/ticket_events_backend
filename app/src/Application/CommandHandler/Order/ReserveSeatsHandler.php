<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Service\Order\ReservationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ReserveSeatsHandler implements CommandHandlerInterface
{
    public function __construct(
        private ReservationService $reservationService,
    ) {
    }

    public function __invoke(ReserveSeatsCommand $command): void
    {
        $this->reservationService->reserve($command->userId, $command->eventSeatIds);
    }
}
