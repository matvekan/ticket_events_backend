<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Venue;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Venue\AddSeatsToVenueCommand;
use App\Application\Dto\SeatData;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\SeatType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api/venues/{id}/seats', name: 'venue.add_seats', methods: ['POST'])]
final class AddSeatsToVenueController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = $request->toArray();

        $seats = [];
        foreach ($data['seats'] ?? [] as $seat) {
            if (!is_array($seat) || !isset($seat['row'], $seat['number'], $seat['type'])) {
                throw new BusinessRuleViolationException('Each seat must contain "row", "number" and "type".');
            }

            if (SeatType::tryFrom((string) $seat['type']) === null) {
                throw new BusinessRuleViolationException(sprintf(
                    'Invalid seat type "%s". Allowed types: %s.',
                    (string) $seat['type'],
                    implode(', ', SeatType::validTypes()),
                ));
            }

            $seats[] = new SeatData(
                row: (string) $seat['row'],
                number: (int) $seat['number'],
                type: (string) $seat['type'],
                sector: isset($seat['sector']) ? (string) $seat['sector'] : null,
            );
        }

        $this->commandBus->dispatch(new AddSeatsToVenueCommand(
            venueId: Uuid::fromRfc4122($id),
            seats: $seats,
        ));

        return new JsonResponse(['message' => 'Seats added.'], JsonResponse::HTTP_CREATED);
    }
}
