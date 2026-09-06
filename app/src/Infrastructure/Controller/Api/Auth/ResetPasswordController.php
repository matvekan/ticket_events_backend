<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Auth;

use App\Application\Command\Auth\ResetPasswordCommand;
use App\Application\Command\CommandBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/reset-password', name: 'auth.reset_password', methods: ['POST'])]
#[OA\Tag(name: 'Auth')]
#[OA\Post(
    path: '/api/auth/reset-password',
    summary: 'Reset password with token',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token', 'password'],
            properties: [
                new OA\Property(property: 'token', type: 'string', example: 'reset_token_from_email'),
                new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'newsecret123'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Password reset successful'),
        new OA\Response(response: 400, description: 'Invalid or expired token'),
        new OA\Response(response: 422, description: 'Validation failed'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
final class ResetPasswordController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] ResetPasswordCommand $command): JsonResponse
    {
        $this->commandBus->dispatch($command);

        return new JsonResponse(['message' => 'Password has been reset successfully.']);
    }
}
