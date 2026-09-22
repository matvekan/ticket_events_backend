<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Order;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Order\RefundTicketsCommand;
use App\Infrastructure\Security\DomainUserAdapter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[OA\Tag(name: 'Orders')]
#[OA\Parameter(name: 'id', description: 'Order UUID', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
#[OA\Post(
    path: '/api/orders/{id}/refund-tickets',
    summary: 'Partially refund tickets in an order',
    security: [['BearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['ticketIds'],
            properties: [
                new OA\Property(
                    property: 'ticketIds',
                    type: 'array',
                    items: new OA\Items(type: 'string', format: 'uuid'),
                    example: ['a1b2c3d4-e5f6-7890-abcd-ef1234567890'],
                    minItems: 1
                ),
            ]
        )
    ),
    tags: ['Orders'],
    responses: [
        new OA\Response(response: 200, description: 'Tickets refunded successfully'),
        new OA\Response(response: 400, description: 'Invalid request payload'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden (Not owner)'),
        new OA\Response(response: 409, description: 'Business rule violation (e.g. less than 24 hours)'),
    ]
)]
#[Route('/api/orders/{id}/refund-tickets', name: 'order.refund_tickets', methods: ['POST'])]
final class RefundTicketsController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly Security $security,
    ) {
    }

    public function __invoke(string $id, Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $ticketIds = $payload['ticketIds'] ?? [];

        if (!\is_array($ticketIds) || $ticketIds === []) {
            throw new BadRequestHttpException('ticketIds must be a non-empty array of ticket identifiers.');
        }

        $user = $this->security->getUser();
        $userId = null;
        if ($user instanceof DomainUserAdapter) {
            $userId = $user->id()->toString();
        } elseif ($user !== null) {
            throw new AccessDeniedException('Access denied.');
        }
        $this->commandBus->dispatch(new RefundTicketsCommand(
            orderId: $id,
            userId: $userId,
            ticketIds: $ticketIds,
        ));

        return new JsonResponse(['message' => 'Tickets refunded successfully.']);
    }
}
