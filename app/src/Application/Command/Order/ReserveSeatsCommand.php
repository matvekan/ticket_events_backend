<?php

declare(strict_types=1);

namespace App\Application\Command\Order;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class ReserveSeatsCommand implements CommandInterface
{
    public readonly Uuid $parsedUserId;
    /** @var Uuid[] */
    public readonly array $parsedEventSeatIds;

    /** @param string[] $eventSeatIds */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $userId,

        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\Uuid()])]
        public readonly array $eventSeatIds,
    ) {
        $this->parsedUserId = Uuid::fromString($userId);
        $this->parsedEventSeatIds = array_map(static fn (string $id) => Uuid::fromString($id), $eventSeatIds);
    }

    public function userId(): Uuid
    {
        return $this->parsedUserId;
    }

    /** @return Uuid[] */
    public function eventSeatIds(): array
    {
        return $this->parsedEventSeatIds;
    }
}
