<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Query\Admin\GetTicketVerificationQuery;
use App\Application\Query\QueryBusInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/tickets/{code}', name: 'admin.verify_ticket', methods: ['GET'])]
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