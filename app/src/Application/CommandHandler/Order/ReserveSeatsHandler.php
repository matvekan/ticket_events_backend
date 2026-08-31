<?php

declare(strict_types=1);

namespace App\Application\CommandHandler\Order;

use App\Application\Command\CommandHandlerInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Application\Service\Order\ReserveSeatsUseCaseInterface;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ReserveSeatsHandler implements CommandHandlerInterface
{
    public function __construct(
        private ReserveSeatsUseCaseInterface $reserveSeatsUseCase,
    ) {}

    public function __invoke(ReserveSeatsCommand $command): void
    {
        $userId = new UserId($command->userId);
        $eventSeatIds = array_map(
            static fn (string $id): EventSeatId => new EventSeatId($id),
            $command->eventSeatIds,
        );

        $this->reserveSeatsUseCase->execute($userId, $eventSeatIds);
    }
}