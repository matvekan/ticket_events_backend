<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Dto\TicketVerificationDto;
use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/tickets/{code}', name: 'admin.verify_ticket', methods: ['GET'])]
#[OA\Tag(name: 'Admin')]
#[OA\Parameter(name: 'code', description: 'Ticket code', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
#[OA\Get(
    path: '/api/admin/tickets/{code}',
    summary: 'Verify ticket by code (admin)',
    tags: ['Admin'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Ticket verification result', content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'valid', type: 'boolean'),
                new OA\Property(property: 'reason', type: 'string'),
                new OA\Property(property: 'ticket', ref: '#/components/schemas/TicketVerification'),
            ]
        )),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class VerifyTicketController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $code): JsonResponse
    {
        $verification = $this->queryBus->dispatch(new GetTicketVerificationQuery($code));
        assert($verification instanceof TicketVerificationDto);

        return new JsonResponse([
            'valid' => $verification->valid,
            'reason' => $verification->reason,
            'ticket' => $verification->ticket,
        ]);
    }
}
