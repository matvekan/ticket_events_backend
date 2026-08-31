<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryBusInterface;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
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
    private const RATE_LIMIT = 30;
    private const RATE_WINDOW_SECONDS = 60;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function __invoke(string $code, Request $request): JsonResponse
    {
        $this->enforceRateLimit($request);

        $verification = $this->queryBus->dispatch(new GetTicketVerificationQuery($code));

        return new JsonResponse([
            'valid' => $verification->valid,
            'reason' => $verification->reason,
            'ticket' => $verification->ticket,
        ]);
    }

    private function enforceRateLimit(Request $request): void
    {
        $key = 'verify_ticket_'.($request->getClientIp() ?? 'unknown');
        $item = $this->cache->getItem(md5($key));

        $data = $item->isHit() ? $item->get() : ['count' => 0, 'reset' => time() + self::RATE_WINDOW_SECONDS];
        if (time() >= $data['reset']) {
            $data = ['count' => 0, 'reset' => time() + self::RATE_WINDOW_SECONDS];
        }
        $data['count']++;

        $item->set($data);
        $item->expiresAfter(self::RATE_WINDOW_SECONDS);
        $this->cache->save($item);

        if ($data['count'] > self::RATE_LIMIT) {
            throw new TooManyRequestsHttpException(self::RATE_WINDOW_SECONDS, 'Too many ticket verification attempts. Try again later.');
        }
    }
}