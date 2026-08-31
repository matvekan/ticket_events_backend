<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\ReserveSeatsCommand;
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
    tags: ['Orders'],
    security: [['BearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['seatIds'],
            properties: [
                new OA\Property(
                    property: 'seatIds',
                    type: 'array',
                    minItems: 1,
                    items: new OA\Items(type: 'string', format: 'uuid'),
                    example: ['a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'b2c3d4e5-f6a7-8901-bcde-f23456789012']
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Seats reserved successfully'),
        new OA\Response(response: 400, description: 'Invalid request'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 422, description: 'Validation failed or seats unavailable'),
    ]
)]
final class ReserveSeatsController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $seatIds = $payload['seatIds'] ?? [];

        if (!is_array($seatIds)) {
            throw new BadRequestHttpException('seatIds must be an array of seat identifiers.');
        }

        $this->commandBus->dispatch(new ReserveSeatsCommand(
            userId: $this->security->getUser()->id()->toRfc4122(),
            eventSeatIds: $seatIds,
        ));

        return new JsonResponse(['message' => 'Seats reserved.'], JsonResponse::HTTP_CREATED);
    }
}
