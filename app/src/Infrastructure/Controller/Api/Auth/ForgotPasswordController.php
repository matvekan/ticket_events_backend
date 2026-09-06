<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Auth;

use App\Application\Command\Auth\ForgotPasswordCommand;
use App\Application\Command\CommandBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/forgot-password', name: 'auth.forgot_password', methods: ['POST'])]
#[OA\Tag(name: 'Auth')]
#[OA\Post(
    path: '/api/auth/forgot-password',
    summary: 'Request password reset',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'If user exists, reset link sent'),
        new OA\Response(response: 422, description: 'Validation failed'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class ForgotPasswordController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] ForgotPasswordCommand $command): JsonResponse
    {
        $this->commandBus->dispatch($command);

        return new JsonResponse(['message' => 'If a user with this email exists, a reset link has been sent.']);
    }
}
