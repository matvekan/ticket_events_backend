<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
use App\Infrastructure\Controller\Api\Shared\RequiresDomainUserTrait;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders', name: 'order.reserve', methods: ['POST'])]
#[OA\Tag(name: 'Orders')]
#[OA\Post(
    path: '/api/orders',
    summary: 'Reserve seats for an event',
    security: [['BearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['seatIds'],
            properties: [
                new OA\Property(
                    property: 'seatIds',
                    type: 'array',
                    items: new OA\Items(type: 'string', format: 'uuid'),
                    example: ['a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'b2c3d4e5-f6a7-8901-bcde-f23456789012'],
                    minItems: 1
                ),
            ]
        )
    ),
    tags: ['Orders'],
    responses: [
        new OA\Response(response: 201, description: 'Seats reserved successfully'),
        new OA\Response(response: 400, description: 'Invalid request'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 422, description: 'Validation failed or seats unavailable'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class ReserveSeatsController extends AbstractController
{
    use RequiresDomainUserTrait;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $seatIdsRaw = $payload['seatIds'] ?? [];

        if (!is_array($seatIdsRaw)) {
            throw new BadRequestHttpException('seatIds must be an array of seat identifiers.');
        }

        /** @var array<int, string> $seatIds */
        $seatIds = [];
        foreach ($seatIdsRaw as $seatId) {
            if (!is_string($seatId) || $seatId === '') {
                throw new BadRequestHttpException('seatIds must be an array of seat identifiers.');
            }

            $seatIds[] = $seatId;
        }

        $userId = $this->getDomainUser($this->security)->id()->toString();
        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $userId,
            eventSeatIds: $seatIds,
        ));

        return new JsonResponse(['message' => 'Seats reserved.'], JsonResponse::HTTP_CREATED);
    }
}
