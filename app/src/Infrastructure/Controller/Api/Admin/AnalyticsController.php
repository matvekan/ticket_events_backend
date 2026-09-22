<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use App\Application\Port\AnalyticsRepositoryInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[OA\Tag(name: 'Admin')]
#[OA\Get(
    path: '/api/admin/analytics',
    summary: 'Get analytics dashboard data',
    tags: ['Admin', 'Analytics'],
    security: [['BearerAuth' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Analytics data',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'totals', ref: '#/components/schemas/AnalyticsTotals'),
                    new OA\Property(property: 'byDay', type: 'array', items: new OA\Items(ref: '#/components/schemas/AnalyticsByDay')),
                    new OA\Property(property: 'topUsers', type: 'array', items: new OA\Items(ref: '#/components/schemas/TopUser')),
                    new OA\Property(property: 'recentPayments', type: 'array', items: new OA\Items(ref: '#/components/schemas/RecentPayment')),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 429, description: 'Too many requests'),
        new OA\Response(response: 503, description: 'Analytics unavailable'),
    ]
)]
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
        } catch (Throwable $exception) {
            return new JsonResponse(
                ['message' => 'Analytics unavailable: '.$exception->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }
    }
}
