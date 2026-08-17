<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/tickets/{code}', name: 'admin.verify_ticket', methods: ['GET'])]
final class VerifyTicketController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    public function __invoke(string $code): JsonResponse
    {
        $verification = $this->queryBus->dispatch(new GetTicketVerificationQuery($code));

        return new JsonResponse([
            'valid' => $verification->valid,
            'reason' => $verification->reason,
            'ticket' => $verification->ticket,
        ]);
    }
}