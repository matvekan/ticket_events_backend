<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Port\AnalyticsRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/analytics', name: 'admin.analytics', methods: ['GET'])]
final class AnalyticsController
{
    public function __construct(
        private readonly AnalyticsRepositoryInterface $analytics,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            return new JsonResponse([
                'totals' => $this->analytics->totals(),
                'byDay' => $this->analytics->byDay(),
                'topUsers' => $this->analytics->topUsers(),
                'recentPayments' => $this->analytics->recentPayments(),
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse(
                ['message' => 'Analytics unavailable: ' . $exception->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }
    }
}
