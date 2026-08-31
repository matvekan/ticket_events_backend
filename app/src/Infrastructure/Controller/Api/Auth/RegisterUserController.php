<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\CommandBusInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/register', name: 'auth.register', methods: ['POST'])]
#[OA\Tag(name: 'Auth')]
#[OA\Post(
    path: '/api/auth/register',
    summary: 'Register a new user',
    tags: ['Auth'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 50, example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'secret123'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'User registered successfully'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
final class RegisterUserController
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] RegisterUserCommand $command): JsonResponse
    {
        $this->commandBus->dispatch($command);

        return new JsonResponse(['message' => 'User registered successfully.'], JsonResponse::HTTP_CREATED);
    }
}
