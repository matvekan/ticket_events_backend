<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Command\CommandBusInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/register', name: 'auth.register', methods: ['POST'])]
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
